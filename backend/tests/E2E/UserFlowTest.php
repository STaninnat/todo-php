<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\Api\Request;
use App\Api\Router;
use App\Api\RouterApp;
use App\DB\Database;
use App\Utils\Logger;
use App\Utils\NativeFileSystem;
use App\Utils\SystemClock;
use App\Utils\CookieManager;
use Tests\Integration\Api\Helper\TestCookieStorage;

/**
 * Class UserFlowTest
 *
 * Verifies the core user flow: Signup -> Signin -> Create Task -> Get Tasks.
 * Uses internal dispatching to test the full backend stack without a web server.
 *
 * @package Tests\E2E
 */
class UserFlowTest extends TestCase
{
    /** @var RouterApp Application instance with internal dispatching */
    private RouterApp $app;

    /** @var TestCookieStorage In-memory cookie storage for token handling */
    private TestCookieStorage $cookieStorage;

    /**
     * Set up the full application stack before each test.
     *
     * Instantiates Router, Logger, Database, and RouterApp.
     * Injects TestCookieStorage to capture authentication tokens.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate the full application stack
        $router = new Router();
        // Use a real logger but maybe silence output or log to a test file?
        // For now, we use stdout/stderr which PHPUnit captures.
        $logger = new Logger(new NativeFileSystem(), new SystemClock(), true);

        // Use the real database connection (already configured via env vars in bootstrap)
        $database = new Database();

        // Use TestCookieStorage to capture cookies
        $this->cookieStorage = new TestCookieStorage();
        $cookieManager = new CookieManager($this->cookieStorage);

        // RouterApp automatically wires controllers and services
        // Pass our custom CookieManager
        $this->app = new RouterApp(
            $router,
            $logger,
            $database,
            '/v1',
            null,
            null,
            null,
            $cookieManager
        );
    }

    /**
     * Test the complete user lifecycle.
     *
     * Scenarios covered:
     * 1. User Signup
     * 2. User Signin (verifies token cookie)
     * 3. Create Task (authenticated)
     * 4. Get Tasks (authenticated)
     * 5. Update Task
     * 6. Mark Task Done
     * 7. Delete Task
     * 8. Verify Deletion
     *
     * @return void
     */
    public function testFullUserLifecycle(): void
    {
        $email = 'e2e_' . uniqid() . '@example.com';
        $password = 'Secret123!';
        $name = 'E2E User';

        // 2. Sign Up
        $reqSignup = new Request(
            'POST',
            '/v1/users/signup',
            [],
            (string) json_encode([
                'username' => 'E2E User',
                'email' => $email,
                'password' => 'Secret123!',
                'password_confirm' => 'Secret123!'
            ])
        );
        $resSignup = $this->app->dispatch($reqSignup, true);
        $this->assertTrue($resSignup['success'] ?? false, 'Signup failed');

        // 3. Sign In
        $reqSignin = new Request(
            'POST',
            '/v1/users/signin',
            [],
            (string) json_encode([
                'username' => 'E2E User',
                'password' => 'Secret123!'
            ])
        );
        $resSignin = $this->app->dispatch($reqSignin, true);

        $this->assertNotNull($resSignin);
        $this->assertTrue($resSignin['success'], 'Signin failed');

        // Token is in cookie, not response body
        $token = $this->cookieStorage->get('access_token');
        $this->assertNotNull($token, 'Access token cookie not found');

        // 3. Create Task
        // We need to simulate the Authorization header.
        // The Request class doesn't have a direct way to set headers in constructor?
        // Let's check Request.php again. It uses $_SERVER['HTTP_AUTHORIZATION'] or apache_request_headers().
        // But for internal dispatch, we might need to mock headers or pass them?
        // Wait, Request constructor doesn't take headers.
        // It reads from $_SERVER.
        // So we need to set $_SERVER['HTTP_AUTHORIZATION'] before creating the request?
        // Or modify Request class to accept headers?
        // Modifying Request class is invasive.
        // Setting $_SERVER is a hack but works for internal dispatch in tests.

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $reqCreate = new Request(
            'POST',
            '/v1/tasks/add', // Note: RouterApp registers /tasks/add, not /tasks for POST? Check RouterApp.php
            [],
            (string) json_encode([
                'title' => 'E2E Task',
                'description' => 'Created via E2E test'
            ])
        );
        // RouterApp.php: ['POST', '/tasks/add', 'addTask', $authMiddlewareFn],

        $resCreate = $this->app->dispatch($reqCreate, true);

        $this->assertNotNull($resCreate);
        $this->assertTrue($resCreate['success'], 'Create task failed: ' . json_encode($resCreate));

        // 4. Get Tasks
        $reqGet = new Request('GET', '/v1/tasks'); // RouterApp: ['GET', '/tasks', 'getTasks', $authMiddlewareFn]
        $resGet = $this->app->dispatch($reqGet, true);

        $this->assertNotNull($resGet);
        $this->assertTrue($resGet['success'], 'Get tasks failed');

        /** @var array{task: array<int, array{id: int, title: string}>} $dataGet */
        $dataGet = $resGet['data'];
        $this->assertCount(1, $dataGet['task']);
        $this->assertSame('E2E Task', $dataGet['task'][0]['title']);
        $taskId = $dataGet['task'][0]['id'];

        // 5. Update Task
        $reqUpdate = new Request(
            'PUT',
            '/v1/tasks/update',
            [],
            (string) json_encode([
                'id' => $taskId,
                'title' => 'Updated E2E Task',
                'description' => 'Updated Desc',
                'is_done' => false
            ])
        );
        $resUpdate = $this->app->dispatch($reqUpdate, true);

        if ($resUpdate === null) {
            $this->fail('Update response is null');
        }
        $this->assertTrue($resUpdate['success'], 'Update task failed');

        /** @var array{task: array{title: string}} $dataUpdate */
        $dataUpdate = $resUpdate['data'];
        $this->assertSame('Updated E2E Task', $dataUpdate['task']['title']);

        // 6. Mark Task Done
        $reqMarkDone = new Request(
            'PUT',
            '/v1/tasks/mark_done',
            [],
            (string) json_encode([
                'id' => $taskId,
                'is_done' => true
            ])
        );
        $resMarkDone = $this->app->dispatch($reqMarkDone, true);

        if ($resMarkDone === null) {
            $this->fail('Mark done response is null');
        }
        $this->assertTrue($resMarkDone['success'], 'Mark done failed');

        /** @var array{task: array{is_done: int}} $dataMarkDone */
        $dataMarkDone = $resMarkDone['data'];
        $this->assertEquals(1, $dataMarkDone['task']['is_done']); // 1 for true in DB usually

        // 7. Delete Task
        $reqDelete = new Request(
            'DELETE',
            '/v1/tasks/delete',
            [],
            (string) json_encode([
                'id' => $taskId
            ])
        );
        $resDelete = $this->app->dispatch($reqDelete, true);

        if ($resDelete === null) {
            $this->fail('Delete response is null');
        }
        $this->assertTrue($resDelete['success'], 'Delete task failed');

        // 8. Verify Deletion
        $reqGet2 = new Request('GET', '/v1/tasks');
        $resGet2 = $this->app->dispatch($reqGet2, true);

        if ($resGet2 === null) {
            $this->fail('Get tasks response is null');
        }

        $this->assertTrue($resGet2['success']);

        /** @var array{task: array<int, mixed>} $data */
        $data = $resGet2['data'];
        $this->assertCount(0, $data['task'], 'Task should be deleted');

        // Clean up
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * Test that login fails with incorrect credentials.
     *
     * Steps:
     * 1. Create a valid user.
     * 2. Attempt signin with wrong password.
     * 3. Verify failure response.
     *
     * @return void
     */
    public function testInvalidLogin(): void
    {
        // 1. Sign Up a user first
        $email = 'fail_' . uniqid() . '@example.com';
        $password = 'Secret123!';

        $reqSignup = new Request('POST', '/v1/users/signup', [], (string) json_encode([
            'username' => 'Fail User',
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password
        ]));
        $this->app->dispatch($reqSignup, true);

        // 2. Try Login with Wrong Password
        $reqSignin = new Request('POST', '/v1/users/signin', [], (string) json_encode([
            'username' => 'Fail User',
            'password' => 'WrongPass'
        ]));
        $resSignin = $this->app->dispatch($reqSignin, true);

        // Expecting failure
        if ($resSignin === null) {
            $this->fail('Signin response is null');
        }
        $this->assertFalse($resSignin['success']);
        // The message might vary, but usually "Invalid username or password."
    }

    /**
     * Test access control for protected endpoints.
     *
     * Verifies that accessing a protected route (e.g., GET /tasks)
     * without a valid authentication token results in an Unauthorized error.
     *
     * @return void
     */
    public function testUnauthorizedAccess(): void
    {
        // Try to access protected route without token
        // Ensure no token in cookie storage
        $this->cookieStorage->delete('access_token');
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $req = new Request('GET', '/v1/tasks');

        // The middleware throws an exception for unauthorized access.
        // Router catches exceptions and returns error JSON.
        // We expect success = false, and maybe 401 status code if we could check it.
        // But dispatch returns the body array.

        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertFalse($res['success']);
        /** @var mixed $msg */
        $msg = $res['message'] ?? '';
        $msgStr = is_string($msg) ? $msg : '';
        $this->assertStringContainsString('Unauthorized', $msgStr);
    }
}
