<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class DeleteUserService
 *
 * Service responsible for handling user deletion logic.
 *
 * - Validates input data (requires `user_id`)
 * - Performs database deletion through {@see UserQueries}
 * - Clears authentication cookies upon successful removal
 *
 * @package App\Api\Auth\Service
 */
class DeleteUserService
{
    /** @var UserQueries Database query handler for user-related operations */
    private UserQueries $userQueries;

    /** @var CookieManager Utility for managing authentication cookies */
    private CookieManager $cookieManager;

    /**
     * Constructor
     *
     * Initializes dependencies required for deleting users.
     *
     * @param UserQueries   $userQueries   Database query handler for user operations
     * @param CookieManager $cookieManager Utility for managing authentication cookies
     */
    public function __construct(UserQueries $userQueries, CookieManager $cookieManager)
    {
        $this->userQueries = $userQueries;
        $this->cookieManager = $cookieManager;
    }


    /**
     * Execute user deletion process.
     *
     * - Validates `user_id` parameter from request
     * - Deletes user record from the database
     * - Ensures operation success and clears authentication token
     *
     * @param Request $req Request object containing input data
     *
     * @throws InvalidArgumentException If `user_id` is missing or invalid
     * @throws RuntimeException         If the deletion operation fails
     *
     * @return void
     */
    public function execute(Request $req): void
    {
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');

        // Attempt to delete user from database
        $result = $this->userQueries->deleteUser($userId);
        RequestValidator::ensureSuccess($result, 'delete user');

        // Clear access token cookies after deletion
        $this->cookieManager->clearAccessToken();
    }
}
