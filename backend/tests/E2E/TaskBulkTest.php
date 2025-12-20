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
 * Class TaskBulkTest
 *
 * Verifies bulk operations on tasks such as bulk delete and bulk mark as done.
 *
 * @package Tests\E2E
 */
class TaskBulkTest extends TestCase
{
    private RouterApp $app;
    private TestCookieStorage $cookieStorage;
    private string $authToken = '';

    /**
     * Set up the test environment.
     *
     * - Initializes the application stack.
     * - Creates and signs in a test user.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $router = new Router();
        $logger = new Logger(new NativeFileSystem(), new SystemClock(), true);
        $database = new Database();

        $this->cookieStorage = new TestCookieStorage();
        $cookieManager = new CookieManager($this->cookieStorage);

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

        $this->loginUser();
    }

    /**
     * Helper to create a user and log them in.
     *
     * @return void
     */
    private function loginUser(): void
    {
        $email = 'bulk_' . uniqid() . '@example.com';
        $password = 'Secret123!';

        // Signup
        $this->app->dispatch(new Request('POST', '/v1/users/signup', [], (string) json_encode([
            'username' => 'Bulk User',
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password
        ])), true);

        // Signin
        $this->app->dispatch(new Request('POST', '/v1/users/signin', [], (string) json_encode([
            'username' => 'Bulk User',
            'password' => $password
        ])), true);

        $token = $this->cookieStorage->get('access_token');
        $this->assertNotNull($token);
        $this->authToken = $token;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->authToken;
    }

    /**
     * Tear down the test environment.
     *
     * Cleans up the authorization header.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }

    /**
     * Helper to create multiple tasks for testing.
     *
     * @param int $count Number of tasks to create
     * @return list<int> List of created task IDs
     */
    private function createTasks(int $count): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $res = $this->app->dispatch(new Request('POST', '/v1/tasks/add', [], (string) json_encode([
                'title' => "Task $i",
                'description' => "Desc $i"
            ])), true);

            if ($res !== null && ($res['success'] ?? false)) {
                /** @var array{task: array{id: int}} $data */
                $data = $res['data'];
                $ids[] = $data['task']['id'];
            }
        }
        return $ids;
    }

    /**
     * Test bulk marking tasks as done.
     *
     * Verifies that multiple tasks can be updated to 'is_done = true' in a single request.
     *
     * @return void
     */
    public function testBulkMarkDone(): void
    {
        $ids = $this->createTasks(3);
        $this->assertCount(3, $ids);

        $req = new Request('PUT', '/v1/tasks/mark_done_bulk', [], (string) json_encode([
            'ids' => $ids,
            'is_done' => true
        ]));
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        /** @var array{count: int} $data */
        $data = $res['data'];
        $this->assertEquals(3, $data['count']);

        // Verify fetching them shows is_done = 1
        $resGet = $this->app->dispatch(new Request('GET', '/v1/tasks'), true);
        $this->assertNotNull($resGet);

        /** @var array{task: list<array{id: int, is_done: int}>} $dataGet */
        $dataGet = $resGet['data'];
        $tasks = $dataGet['task'];

        foreach ($tasks as $task) {
            if (in_array($task['id'], $ids)) {
                $this->assertEquals(1, $task['is_done']);
            }
        }
    }

    /**
     * Test bulk deleting tasks.
     *
     * Verifies that multiple tasks can be deleted in a single request.
     *
     * @return void
     */
    public function testBulkDelete(): void
    {
        $ids = $this->createTasks(3);
        $this->assertCount(3, $ids);

        $req = new Request('DELETE', '/v1/tasks/delete_bulk', [], (string) json_encode([
            'ids' => $ids
        ]));
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        /** @var array{count: int} $data */
        $data = $res['data'];
        $this->assertEquals(3, $data['count']);

        // Verify they are gone
        $resGet = $this->app->dispatch(new Request('GET', '/v1/tasks'), true);
        $this->assertNotNull($resGet);

        /** @var array{task: list<array{id: int}>} $dataGet */
        $dataGet = $resGet['data'];
        $tasks = $dataGet['task'];

        foreach ($tasks as $task) {
            $this->assertNotContains($task['id'], $ids);
        }
    }

    /**
     * Test bulk delete with an empty list.
     *
     * Expected: Success with 0 count.
     *
     * @return void
     */
    public function testBulkDeleteEmpty(): void
    {
        $req = new Request('DELETE', '/v1/tasks/delete_bulk', [], (string) json_encode([
            'ids' => []
        ]));
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        // Expect success but 0 count
        $this->assertTrue($res['success']);

        /** @var array{count: int} $data */
        $data = $res['data'];
        $this->assertEquals(0, $data['count']);
    }

    /**
     * Test bulk action limit.
     *
     * Verifies that the API enforces a limit on the number of items (max 50).
     *
     * @return void
     */
    public function testBulkActionLimit(): void
    {
        $ids = range(1, 51); // 51 fake IDs

        $req = new Request('DELETE', '/v1/tasks/delete_bulk', [], (string) json_encode([
            'ids' => $ids
        ]));

        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertFalse($res['success']);

        /** @var string $msg */
        $msg = $res['message'] ?? '';
        $this->assertStringContainsString('Cannot delete more than 50 tasks', $msg);
    }
}
