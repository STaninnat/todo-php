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
 * Class UserManagementTest
 *
 * Verifies core user management functionality including retrieving profile (`/me`),
 * updating profile, token refreshing, signing out, and account deletion.
 *
 * @package Tests\E2E
 */
class UserManagementTest extends TestCase
{
    private RouterApp $app;
    private TestCookieStorage $cookieStorage;
    private string $authToken = '';
    private string $userEmail = '';

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
        // Use a silent FileSystem mock to prevent logs from interfering with headers
        $silentFs = new class implements \App\Utils\FileSystemInterface {
            public function write(string $path, string $content, bool $append = true): void
            {
            }
            public function delete(string $path): void
            {
            }
            public function listFiles(string $pattern): array
            {
                return [];
            }
            public function ensureDir(string $path): void
            {
            }
        };
        $logger = new Logger($silentFs, new SystemClock(), false);
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

        // Setup a user for testing
        $this->createAndLoginUser();
    }

    /**
     * Helper to create a user and log them in.
     *
     * @return void
     */
    private function createAndLoginUser(): void
    {
        $email = 'mgmt_' . uniqid() . '@example.com';
        $this->userEmail = $email;
        $password = 'Secret123!';

        // Signup
        $this->app->dispatch(new Request('POST', '/v1/users/signup', [], (string) json_encode([
            'username' => 'Mgmt User',
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password
        ])), true);

        // Signin
        $this->app->dispatch(new Request('POST', '/v1/users/signin', [], (string) json_encode([
            'username' => 'Mgmt User',
            'password' => $password
        ])), true);

        $token = $this->cookieStorage->get('access_token');
        $this->assertNotNull($token);
        $this->authToken = $token;

        // Set auth header for subsequent requests
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
     * Test retrieving current user profile.
     *
     * Verifies `GET /users/me` returns the correct user data.
     *
     * @return void
     */
    public function testGetUserMe(): void
    {
        $req = new Request('GET', '/v1/users/me');
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        // Response payload is directly the data array, not wrapped in 'user'
        // GetUserService returns ['username' => ..., 'email' => ...]
        /** @var array{username: string, email: string} $data */
        $data = $res['data'];

        $this->assertEquals('Mgmt User', $data['username']);
        $this->assertEquals($this->userEmail, $data['email']);
    }

    /**
     * Test updating user profile.
     *
     * Verifies `PUT /users/update` correctly updates the username
     * and persists the change.
     *
     * @return void
     */
    public function testUpdateUser(): void
    {
        $newName = 'Updated Name';
        // Needs email as well
        $req = new Request('PUT', '/v1/users/update', [], (string) json_encode([
            'username' => $newName,
            'email' => $this->userEmail
        ]));
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success'], 'Update failed: ' . (string) json_encode($res['message'] ?? ''));

        /** @var array{username: string} $data */
        $data = $res['data'];
        $this->assertEquals($newName, $data['username']);

        // Verify with Get Me
        $reqGet = new Request('GET', '/v1/users/me');
        $resGet = $this->app->dispatch($reqGet, true);

        $this->assertNotNull($resGet);
        /** @var array{username: string} $dataGet */
        $dataGet = $resGet['data'];
        $this->assertEquals($newName, $dataGet['username']);
    }

    /**
     * Test token refresh.
     *
     * Verifies `POST /users/refresh` successfully issues a new access token
     * when a valid refresh token is present.
     *
     * @return void
     */
    public function testRefreshToken(): void
    {
        // Simulate some time passing or just call refresh
        $req = new Request('POST', '/v1/users/refresh');

        // We need the refresh token in cookies, which should be there from login
        $this->assertNotNull($this->cookieStorage->get('refresh_token'), 'Refresh token missing before refresh');

        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        $newToken = $this->cookieStorage->get('access_token');
        $this->assertNotNull($newToken);
    }

    /**
     * Test signing out.
     *
     * Verifies `POST /users/signout` verifies cookies are cleared and
     * subsequent access is unauthorized.
     *
     * @return void
     */
    public function testSignOut(): void
    {
        $req = new Request('POST', '/v1/users/signout');
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        // Verify cookies are gone or empty
        $this->assertNull($this->cookieStorage->get('access_token'), 'Access token should be removed');
        $this->assertNull($this->cookieStorage->get('refresh_token'), 'Refresh token should be removed');

        // Verify access is denied
        unset($_SERVER['HTTP_AUTHORIZATION']); // Client would remove this

        $reqCheck = new Request('GET', '/v1/users/me');
        $resCheck = $this->app->dispatch($reqCheck, true);

        $this->assertNotNull($resCheck);
        $this->assertFalse($resCheck['success']);
        $this->assertStringContainsString('Unauthorized', (string) json_encode($resCheck['message'] ?? ''));
    }

    /**
     * Test user account deletion.
     *
     * Verifies `DELETE /users/delete` removes the user and
     * prevents subsequent sign-ins.
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        $req = new Request('DELETE', '/v1/users/delete');
        $res = $this->app->dispatch($req, true);

        $this->assertNotNull($res);
        $this->assertTrue($res['success']);

        // Verify cookies are gone
        $this->assertNull($this->cookieStorage->get('access_token'));

        // Verify user cannot login anymore
        $reqSignin = new Request('POST', '/v1/users/signin', [], (string) json_encode([
            'username' => 'Mgmt User',
            'password' => 'Secret123!'
        ]));
        $resSignin = $this->app->dispatch($reqSignin, true);

        // RouterApp::dispatch might catch the error and return success=false
        // but if the user doesn't exist, signin service throws exception
        if ($resSignin !== null) {
            $this->assertFalse($resSignin['success']);
        } else {
            $this->fail('Signin response is null');
        }
    }
}
