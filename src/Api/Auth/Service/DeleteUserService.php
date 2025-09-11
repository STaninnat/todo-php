<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\UserQueries;
use App\Utils\CookieManager;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class DeleteUserService
 *
 * Service responsible for deleting a user from the system.
 * 
 * This service:
 * - Validates the provided user ID.
 * - Attempts to delete the user via database queries.
 * - Clears authentication cookies after successful deletion.
 *
 * @package App\Api\Auth\Service
 */
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
     * Process:
     * - Validate input and ensure a valid user ID is provided.
     * - Perform deletion using UserQueries.
     * - Throw exceptions if the operation fails.
     * - Clear authentication cookies upon success.
     *
     * @param array $input Input array containing the 'user_id' field.
     *
     * @throws InvalidArgumentException If user_id is missing or empty.
     * @throws RuntimeException         If the database operation fails.
     *
     * @return void
     */
    public function execute(array $input): void
    {
        // Sanitize and validate user_id
        $userId = trim(strip_tags($input['user_id'] ?? ''));
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }

        // Attempt to delete user from database
        $result = $this->userQueries->deleteUser($userId);

        // Handle failure case
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to delete user: $errorInfo");
        }

        // Clear access token cookies after deletion
        $this->cookieManager->clearAccessToken();
    }
}
