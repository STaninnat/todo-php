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

    use ApiTestHelperTrait;

    public function testDeleteUserSuccess(): void
    {
        $this->deleteUserService->expects($this->once())->method('execute');

        $req = $this->makeRequest(params: ['id' => 1], method: 'DELETE', path: '/users/1');
        $decoded = $this->controller->deleteUser($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User deleted successfully', $decoded['message']);
    }

    public function testGetUserSuccess(): void
    {
        $userData = ['id' => 1, 'name' => 'John'];
        $this->getUserService->method('execute')->willReturn($userData);

        $req = $this->makeRequest(params: ['id' => 1], method: 'GET', path: '/users/1');
        $decoded = $this->controller->getUser($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User retrieved successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    public function testSigninSuccess(): void
    {
        $this->signinService->expects($this->once())->method('execute');

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signin');
        $decoded = $this->controller->signin($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signin successfully', $decoded['message']);
    }

    public function testSignoutSuccess(): void
    {
        $this->signoutService->expects($this->once())->method('execute');

        $req = $this->makeRequest(method: 'POST', path: '/auth/signout');
        $decoded = $this->controller->signout($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signed out successfully.', $decoded['message']);
    }

    public function testSignupSuccess(): void
    {
        $this->signupService->expects($this->once())->method('execute');

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signup');
        $decoded = $this->controller->signup($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User signup successfully', $decoded['message']);
    }

    public function testUpdateUserSuccess(): void
    {
        $userData = ['id' => 1, 'name' => 'Jane'];
        $this->updateUserService->method('execute')->willReturn($userData);

        $req = $this->makeRequest(['name' => 'Jane'], params: ['id' => 1], method: 'PUT', path: '/users/1');
        $decoded = $this->controller->updateUser($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('User updated successfully', $decoded['message']);
        $this->assertSame($userData, $decoded['data']);
    }

    // Exception tests
    public function testDeleteUserThrowsException(): void
    {
        $this->deleteUserService->method('execute')->willThrowException(new RuntimeException('Delete failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'DELETE', path: '/users/1');
        $this->controller->deleteUser($req, true);
    }

    public function testGetUserThrowsException(): void
    {
        $this->getUserService->method('execute')->willThrowException(new RuntimeException('Get failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'GET', path: '/users/1');
        $this->controller->getUser($req, true);
    }

    public function testSigninThrowsException(): void
    {
        $this->signinService->method('execute')->willThrowException(new RuntimeException('Signin failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signin');
        $this->controller->signin($req, true);
    }

    public function testSignoutThrowsException(): void
    {
        $this->signoutService->method('execute')->willThrowException(new RuntimeException('Signout failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(method: 'POST', path: '/auth/signout');
        $this->controller->signout($req, true);
    }

    public function testSignupThrowsException(): void
    {
        $this->signupService->method('execute')->willThrowException(new RuntimeException('Signup failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['email' => 'a@b.com', 'password' => 'pass'], method: 'POST', path: '/auth/signup');
        $this->controller->signup($req, true);
    }

    public function testUpdateUserThrowsException(): void
    {
        $this->updateUserService->method('execute')->willThrowException(new RuntimeException('Update failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['name' => 'Jane'], params: ['id' => 1], method: 'PUT', path: '/users/1');
        $this->controller->updateUser($req, true);
    }
}
