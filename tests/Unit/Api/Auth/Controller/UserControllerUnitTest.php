<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Controller;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Controller\UserController;
use App\Api\Auth\Service\DeleteUserService;
use App\Api\Auth\Service\GetUserService;
use App\Api\Auth\Service\SigninService;
use App\Api\Auth\Service\SignoutService;
use App\Api\Auth\Service\SignupService;
use App\Api\Auth\Service\UpdateUserService;
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use RuntimeException;

/**
 * Class UserControllerTest
 *
 * Unit tests for the UserController class.
 *
 * Covers behavior for all major user-related API endpoints:
 * - Signup / Signin / Signout
 * - Get / Update / Delete user
 *
 * Verifies both successful responses and exception scenarios
 * to ensure robust controller behavior.
 *
 * Uses PHPUnit mocks for dependency isolation.
 *
 * @package Tests\Unit\Api\Auth\Controller
 */
class UserControllerUnitTest extends TestCase
{
    /** @var DeleteUserService&\PHPUnit\Framework\MockObject\MockObject Mock for delete user service */
    private $deleteUserService;

    /** @var GetUserService&\PHPUnit\Framework\MockObject\MockObject Mock for get user service */
    private $getUserService;

    /** @var SigninService&\PHPUnit\Framework\MockObject\MockObject Mock for signin service */
    private $signinService;

    /** @var SignoutService&\PHPUnit\Framework\MockObject\MockObject Mock for signout service */
    private $signoutService;

    /** @var SignupService&\PHPUnit\Framework\MockObject\MockObject Mock for signup service */
    private $signupService;

    /** @var UpdateUserService&\PHPUnit\Framework\MockObject\MockObject Mock for update user service */
    private $updateUserService;

    /** @var UserController The controller instance under test */
    private UserController $controller;

    /**
     * Sets up all required mocks and the controller instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize all service mocks
        $this->deleteUserService = $this->createMock(DeleteUserService::class);
        $this->getUserService = $this->createMock(GetUserService::class);
        $this->signinService = $this->createMock(SigninService::class);
        $this->signoutService = $this->createMock(SignoutService::class);
        $this->signupService = $this->createMock(SignupService::class);
        $this->updateUserService = $this->createMock(UpdateUserService::class);

        // Inject mocks into UserController
        $this->controller = new UserController(
            $this->deleteUserService,
            $this->getUserService,
            $this->signinService,
            $this->signoutService,
            $this->signupService,
            $this->updateUserService
        );
    }

    // Use helper for easy request creation
    use ApiTestHelperTrait;

    /**
     * Test successful user deletion flow.
     *
     * @return void
     */
    public function testDeleteUserSuccess(): void
    {
        // Expect the delete service to be executed once
        $this->deleteUserService->expects($this->once())->method('execute');

        // Simulate DELETE request to /users/1
        $req = $this->makeRequest(params: ['id' => 1], method: 'DELETE', path: '/users/1');
        $decoded = $this->controller->deleteUser($req, true);

        // Validate response structure
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User deleted successfully', $decoded['message']);
    }

    /**
     * Test successful user retrieval flow.
     *
     * @return void
     */
    public function testGetUserSuccess(): void
    {
        // Mock returned user data
        $userData = ['id' => 1, 'name' => 'John'];
        $this->getUserService->method('execute')->willReturn($userData);

        // Simulate GET request to /users/1
        $req = $this->makeRequest(params: ['id' => 1], method: 'GET', path: '/users/1');
        $decoded = $this->controller->getUser($req, true);

        // Validate success response with data
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User retrieved successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    /**
     * Test successful signin flow.
     *
     * @return void
     */
    public function testSigninSuccess(): void
    {
        $this->signinService->expects($this->once())->method('execute');

        // POST /auth/signin with valid credentials
        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signin');
        $decoded = $this->controller->signin($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signin successfully', $decoded['message']);
    }

    /**
     * Test successful signout flow.
     *
     * @return void
     */
    public function testSignoutSuccess(): void
    {
        $this->signoutService->expects($this->once())->method('execute');

        // POST /auth/signout
        $req = $this->makeRequest(method: 'POST', path: '/auth/signout');
        $decoded = $this->controller->signout($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signed out successfully.', $decoded['message']);
    }

    /**
     * Test successful signup flow.
     *
     * @return void
     */
    public function testSignupSuccess(): void
    {
        $this->signupService->expects($this->once())->method('execute');

        // POST /auth/signup with user credentials
        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signup');
        $decoded = $this->controller->signup($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signup successfully', $decoded['message']);
    }

    /**
     * Test successful user update flow.
     *
     * @return void
     */
    public function testUpdateUserSuccess(): void
    {
        $userData = ['id' => 1, 'name' => 'Jane'];
        $this->updateUserService->method('execute')->willReturn($userData);

        // PUT /users/1 with updated name
        $req = $this->makeRequest(['name' => 'Jane'], params: ['id' => 1], method: 'PUT', path: '/users/1');
        $decoded = $this->controller->updateUser($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User updated successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    // ==========================================================
    // 💥 Exception tests: ensure proper propagation of failures
    // ==========================================================

    /**
     * Test deleteUser() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testDeleteUserThrowsException(): void
    {
        $this->deleteUserService->method('execute')->willThrowException(new RuntimeException('Delete failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'DELETE', path: '/users/1');
        $this->controller->deleteUser($req, true);
    }

    /**
     * Test getUser() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testGetUserThrowsException(): void
    {
        $this->getUserService->method('execute')->willThrowException(new RuntimeException('Get failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'GET', path: '/users/1');
        $this->controller->getUser($req, true);
    }

    /**
     * Test signin() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testSigninThrowsException(): void
    {
        $this->signinService->method('execute')->willThrowException(new RuntimeException('Signin failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signin');
        $this->controller->signin($req, true);
    }

    /**
     * Test signout() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testSignoutThrowsException(): void
    {
        $this->signoutService->method('execute')->willThrowException(new RuntimeException('Signout failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(method: 'POST', path: '/auth/signout');
        $this->controller->signout($req, true);
    }

    /**
     * Test signup() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testSignupThrowsException(): void
    {
        $this->signupService->method('execute')->willThrowException(new RuntimeException('Signup failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signup');
        $this->controller->signup($req, true);
    }

    /**
     * Test updateUser() throws RuntimeException on failure.
     *
     * @return void
     */
    public function testUpdateUserThrowsException(): void
    {
        $this->updateUserService->method('execute')->willThrowException(new RuntimeException('Update failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['name' => 'Jane'], params: ['id' => 1], method: 'PUT', path: '/users/1');
        $this->controller->updateUser($req, true);
    }
}
