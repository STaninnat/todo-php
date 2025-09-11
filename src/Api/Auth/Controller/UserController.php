<?php

declare(strict_types=1);

namespace App\Api\Auth\Controller;

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

    /**
     * Delete a user from the system.
     *
     * @param array $input   Input data containing user identification.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response if in test mode, otherwise null.
     */
    public function deleteUser(array $input, bool $forTest = false): ?array
    {
        // Execute user deletion
        $this->deleteUserService->execute($input);

        // Build response
        $response = JsonResponder::quickSuccess('User deleted successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Retrieve user data.
     *
     * @param array $input   Input data containing user identification.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with user data in test mode, otherwise null.
     */
    public function getUser(array $input, bool $forTest = false): ?array
    {
        // Fetch user data
        $data = $this->getUserService->execute($input);

        // Build response with payload
        $response = JsonResponder::success('User retrieved successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Sign in a user.
     *
     * @param array $input   Input data containing login credentials.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response if in test mode, otherwise null.
     */
    public function signin(array $input, bool $forTest = false): ?array
    {
        // Execute sign-in process
        $this->signinService->execute($input);

        // Build response
        $response = JsonResponder::quickSuccess('User signin successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Sign out a user.
     *
     * @param bool $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response if in test mode, otherwise null.
     */
    public function signout(bool $forTest = false): ?array
    {
        // Execute sign-out process
        $this->signoutService->execute();

        // Build response
        $response = JsonResponder::quickSuccess('User signed out successfully.', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Sign up a new user.
     *
     * @param array $input   Input data for creating a new user.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response if in test mode, otherwise null.
     */
    public function signup(array $input, bool $forTest = false): ?array
    {
        // Execute sign-up process
        $this->signupService->execute($input);

        // Build response
        $response = JsonResponder::quickSuccess('User signup successfully', false, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Update an existing user’s data.
     *
     * @param array $input   Input data containing updated user information.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with updated data in test mode, otherwise null.
     */
    public function updateUser(array $input, bool $forTest = false): ?array
    {
        // Execute update
        $data = $this->updateUserService->execute($input);

        // Build response with payload
        $response = JsonResponder::success('User updated successfully')
            ->withPayload($data)
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
