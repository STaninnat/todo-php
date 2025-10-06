<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

class DeleteUserService
{
    private UserQueries $userQueries;
    private CookieManager $cookieManager;

    /**
     * Constructor
     *
     * @param UserQueries   $userQueries   Database query handler for user operations.
     * @param CookieManager $cookieManager Utility for managing authentication cookies.
     */
    public function __construct(UserQueries $userQueries, CookieManager $cookieManager)
    {
        $this->userQueries = $userQueries;
        $this->cookieManager = $cookieManager;
    }

    /**
     * Execute the deletion of a user.
     *
     * @param Request $req Request object containing input data.
     *
     * @throws InvalidArgumentException If user_id is missing or empty.
     * @throws RuntimeException         If the database operation fails.
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
