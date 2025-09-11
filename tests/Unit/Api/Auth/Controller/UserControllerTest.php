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
use RuntimeException;

/**
 * Class UserControllerTest
 *
 * Unit tests for the UserController class.
 *
 * This test suite verifies:
 * - Success responses from each controller method
 * - Exception handling when services fail
 *
 * All service dependencies are mocked to avoid real DB or authentication calls.
 *
 * @package Tests\Unit\Api\Auth\Controller
 */
class UserControllerTest extends TestCase
{
    /** @var DeleteUserService&\PHPUnit\Framework\MockObject\MockObject */
    private $deleteUserService;

    /** @var GetUserService&\PHPUnit\Framework\MockObject\MockObject */
    private $getUserService;

    /** @var SigninService&\PHPUnit\Framework\MockObject\MockObject */
    private $signinService;

    /** @var SignoutService&\PHPUnit\Framework\MockObject\MockObject */
    private $signoutService;

    /** @var SignupService&\PHPUnit\Framework\MockObject\MockObject */
    private $signupService;

    /** @var UpdateUserService&\PHPUnit\Framework\MockObject\MockObject */
    private $updateUserService;

    private UserController $controller;

    /**
     * Setup mocks and controller instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteUserService = $this->createMock(DeleteUserService::class);
        $this->getUserService = $this->createMock(GetUserService::class);
        $this->signinService = $this->createMock(SigninService::class);
        $this->signoutService = $this->createMock(SignoutService::class);
        $this->signupService = $this->createMock(SignupService::class);
        $this->updateUserService = $this->createMock(UpdateUserService::class);

        $this->controller = new UserController(
            $this->deleteUserService,
            $this->getUserService,
            $this->signinService,
            $this->signoutService,
            $this->signupService,
            $this->updateUserService
        );
    }

    // --------------------------------
    // Success cases with $forTest = true
    // --------------------------------

    /**
     * Test successful deletion of a user.
     *
     * @return void
     */
    public function testDeleteUserSuccess(): void
    {
        $this->deleteUserService->expects($this->once())->method('execute');

        $decoded = $this->controller->deleteUser(['id' => 1], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User deleted successfully', $decoded['message']);
    }

    /**
     * Test successful retrieval of a user.
     *
     * @return void
     */
    public function testGetUserSuccess(): void
    {
        $userData = ['id' => 1, 'name' => 'John'];
        $this->getUserService->method('execute')->willReturn($userData);

        $decoded = $this->controller->getUser(['id' => 1], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User retrieved successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    /**
     * Test successful signin of a user.
     *
     * @return void
     */
    public function testSigninSuccess(): void
    {
        $this->signinService->expects($this->once())->method('execute');

        $decoded = $this->controller->signin(['email' => 'a@b.com', 'password' => 'pass'], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signin successfully', $decoded['message']);
    }

    /**
     * Test successful signout of a user.
     *
     * @return void
     */
    public function testSignoutSuccess(): void
    {
        $this->signoutService->expects($this->once())->method('execute');

        $decoded = $this->controller->signout(true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signed out successfully.', $decoded['message']);
    }

    /**
     * Test successful signup of a user.
     *
     * @return void
     */
    public function testSignupSuccess(): void
    {
        $this->signupService->expects($this->once())->method('execute');

        $decoded = $this->controller->signup(['email' => 'a@b.com', 'password' => 'pass'], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signup successfully', $decoded['message']);
    }

    /**
     * Test successful update of a user.
     *
     * @return void
     */
    public function testUpdateUserSuccess(): void
    {
        $userData = ['id' => 1, 'name' => 'Jane'];
        $this->updateUserService->method('execute')->willReturn($userData);

        $decoded = $this->controller->updateUser(['id' => 1, 'name' => 'Jane'], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User updated successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    // ------------------------------
    // Exception cases
    // ------------------------------

    /**
     * Test that deleteUser() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testDeleteUserThrowsException(): void
    {
        $this->deleteUserService->method('execute')
            ->willThrowException(new RuntimeException('Delete failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->deleteUser(['id' => 1], true);
    }

    /**
     * Test that getUser() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testGetUserThrowsException(): void
    {
        $this->getUserService->method('execute')
            ->willThrowException(new RuntimeException('Get failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->getUser(['id' => 1], true);
    }

    /**
     * Test that signin() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testSigninThrowsException(): void
    {
        $this->signinService->method('execute')
            ->willThrowException(new RuntimeException('Signin failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->signin(['email' => 'a@b.com', 'password' => 'pass'], true);
    }

    /**
     * Test that signout() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testSignoutThrowsException(): void
    {
        $this->signoutService->method('execute')
            ->willThrowException(new RuntimeException('Signout failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->signout(true);
    }

    /**
     * Test that signup() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testSignupThrowsException(): void
    {
        $this->signupService->method('execute')
            ->willThrowException(new RuntimeException('Signup failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->signup(['email' => 'a@b.com', 'password' => 'pass'], true);
    }

    /**
     * Test that updateUser() throws RuntimeException when service fails.
     *
     * @return void
     */
    public function testUpdateUserThrowsException(): void
    {
        $this->updateUserService->method('execute')
            ->willThrowException(new RuntimeException('Update failed'));

        $this->expectException(RuntimeException::class);
        $this->controller->updateUser(['id' => 1, 'name' => 'Jane'], true);
    }
}
