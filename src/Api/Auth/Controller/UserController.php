<?php

declare(strict_types=1);

namespace App\Api\Auth\Controller;

use App\Api\Request;
use App\Api\Auth\Service\DeleteUserService;
use App\Api\Auth\Service\GetUserService;
use App\Api\Auth\Service\SigninService;
use App\Api\Auth\Service\SignoutService;
use App\Api\Auth\Service\SignupService;
use App\Api\Auth\Service\UpdateUserService;
use App\Utils\JsonResponder;

/**
 * Class UserController
 *
 * Controller responsible for handling user-related operations such as
 * sign-in, sign-up, update, retrieval, and deletion.
 *
 * Each method interacts with its corresponding service to process business
 * logic and returns standardized JSON responses via JsonResponder.
 *
 * @package App\Api\Auth\Controller
 */
class UserController
{
    private DeleteUserService $deleteUserService;
    private GetUserService $getUserService;
    private SigninService $signinService;
    private SignoutService $signoutService;
    private SignupService $signupService;
    private UpdateUserService $updateUserService;

    /**
     * Constructor
     *
     * Injects service dependencies responsible for user authentication
     * and management.
     *
     * @param DeleteUserService $deleteUserService Service to delete a user.
     * @param GetUserService    $getUserService    Service to fetch user data.
     * @param SigninService     $signinService     Service to handle user sign-in.
     * @param SignoutService    $signoutService    Service to handle user sign-out.
     * @param SignupService     $signupService     Service to handle user sign-up.
     * @param UpdateUserService $updateUserService Service to update user data.
     */
    public function __construct(
        DeleteUserService $deleteUserService,
        GetUserService $getUserService,
        SigninService $signinService,
        SignoutService $signoutService,
        SignupService $signupService,
        UpdateUserService $updateUserService
    ) {
        $this->deleteUserService = $deleteUserService;
        $this->getUserService = $getUserService;
        $this->signinService = $signinService;
        $this->signoutService = $signoutService;
        $this->signupService = $signupService;
        $this->updateUserService = $updateUserService;
    }

    public function deleteUser(Request $req, bool $forTest = false): ?array
    {
        $this->deleteUserService->execute($req);

        $response = JsonResponder::quickSuccess('User deleted successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    public function getUser(Request $req, bool $forTest = false): ?array
    {
        $data = $this->getUserService->execute($req);

        $response = JsonResponder::success('User retrieved successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    public function signin(Request $req, bool $forTest = false): ?array
    {
        $this->signinService->execute($req);

        $response = JsonResponder::quickSuccess('User signin successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    public function signout(Request $req, bool $forTest = false): ?array
    {
        $this->signoutService->execute();

        $response = JsonResponder::quickSuccess('User signed out successfully.', false, $forTest);

        return $forTest ? $response : null;
    }

    public function signup(Request $req, bool $forTest = false): ?array
    {
        $this->signupService->execute($req);

        $response = JsonResponder::quickSuccess('User signup successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    public function updateUser(Request $req, bool $forTest = false): ?array
    {
        $data = $this->updateUserService->execute($req);

        $response = JsonResponder::success('User updated successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
