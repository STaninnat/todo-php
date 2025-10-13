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
    /** @var DeleteUserService Service for deleting user accounts */
    private DeleteUserService $deleteUserService;

    /** @var GetUserService Service for retrieving user data */
    private GetUserService $getUserService;

    /** @var SigninService Service for handling user authentication (login) */
    private SigninService $signinService;

    /** @var SignoutService Service for handling user logout */
    private SignoutService $signoutService;

    /** @var SignupService Service for registering new users */
    private SignupService $signupService;

    /** @var UpdateUserService Service for updating existing user details */
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

    /**
     * Handle user deletion request.
     *
     * - Delegates logic to {@see DeleteUserService}
     * - Returns a standardized success JSON response
     *
     * @param Request $req     HTTP request
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function deleteUser(Request $req, bool $forTest = false): ?array
    {
        $this->deleteUserService->execute($req);

        // Build a standard JSON success response
        $response = JsonResponder::quickSuccess('User deleted successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle user retrieval request.
     *
     * - Uses {@see GetUserService} to fetch user data
     * - Returns payload with user info
     *
     * @param Request $req     HTTP request
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function getUser(Request $req, bool $forTest = false): ?array
    {
        $data = $this->getUserService->execute($req);

        // Build response with user data
        $response = JsonResponder::success('User retrieved successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle user sign-in.
     *
     * - Authenticates user credentials
     * - May set authentication cookies/tokens
     *
     * @param Request $req     HTTP request containing login credentials
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function signin(Request $req, bool $forTest = false): ?array
    {
        $this->signinService->execute($req);

        $response = JsonResponder::quickSuccess('User signin successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle user sign-out.
     *
     * - Clears authentication session or cookie
     * - Returns confirmation message
     *
     * @param Request $req     HTTP request (unused)
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function signout(Request $req, bool $forTest = false): ?array
    {
        $this->signoutService->execute();

        $response = JsonResponder::quickSuccess('User signed out successfully.', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle user registration (sign-up).
     *
     * - Delegates validation and user creation to {@see SignupService}
     * - Returns a standard success message
     *
     * @param Request $req     HTTP request containing signup data
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function signup(Request $req, bool $forTest = false): ?array
    {
        $this->signupService->execute($req);

        $response = JsonResponder::quickSuccess('User signup successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle user update request.
     *
     * - Delegates logic to {@see UpdateUserService}
     * - Returns updated user data as payload
     *
     * @param Request $req     HTTP request containing updated user info
     * @param bool    $forTest If true, returns array instead of sending JSON
     *
     * @return array|null Response array for tests, or null in production
     */
    public function updateUser(Request $req, bool $forTest = false): ?array
    {
        $data = $this->updateUserService->execute($req);

        // Respond with updated user information
        $response = JsonResponder::success('User updated successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
