<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Controller\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Controller\UserController;
use App\Api\Auth\Service\DeleteUserService;
use App\Api\Auth\Service\GetUserService;
use App\Api\Auth\Service\SigninService;
use App\Api\Auth\Service\SignoutService;
use App\Api\Auth\Service\SignupService;
use App\Api\Auth\Service\UpdateUserService;

/**
 * Class UserControllerTypeErrTest
 *
 * Unit tests for type errors in UserController methods.
 *
 * This test suite ensures that passing invalid argument types
 * to controller methods results in \TypeError exceptions.
 *
 * All service dependencies are mocked to isolate type error tests.
 *
 * @package Tests\Unit\Api\Auth\Controller\TypeError
 */
class UserControllerTypeErrTest extends TestCase
{
    private UserController $controller;

    /**
     * Setup mocks and controller instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $deleteUserService = $this->createMock(DeleteUserService::class);
        $getUserService = $this->createMock(GetUserService::class);
        $signinService = $this->createMock(SigninService::class);
        $signoutService = $this->createMock(SignoutService::class);
        $signupService = $this->createMock(SignupService::class);
        $updateUserService = $this->createMock(UpdateUserService::class);

        $this->controller = new UserController(
            $deleteUserService,
            $getUserService,
            $signinService,
            $signoutService,
            $signupService,
            $updateUserService
        );
    }

    /**
     * Test that deleteUser() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testDeleteUserTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->deleteUser('invalid_type', true);
    }

    /**
     * Test that getUser() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testGetUserTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->getUser('invalid_type', true);
    }

    /**
     * Test that signin() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testSigninTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->signin('invalid_type', true);
    }

    /**
     * Test that signout() throws TypeError when argument is invalid.
     *
     * @return void
     */
    public function testSignoutTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->signout('invalid_type');
    }

    /**
     * Test that signup() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testSignupTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->signup('invalid_type', true);
    }

    /**
     * Test that updateUser() throws TypeError when argument is not an array.
     *
     * @return void
     */
    public function testUpdateUserTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->updateUser('invalid_type', true);
    }
}
