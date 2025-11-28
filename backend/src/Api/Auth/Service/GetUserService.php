<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class GetUserService
 *
 * Service responsible for retrieving user information by ID.
 *
 * - Validates input request (`user_id` required)
 * - Fetches user record via {@see UserQueries}
 * - Returns sanitized user fields
 *
 * @package App\Api\Auth\Service
 */
class GetUserService
{
    /** @var UserQueries Database query handler for user operations */
    private UserQueries $userQueries;

    /**
     * Constructor
     *
     * Initializes dependencies required for retrieving user data.
     *
     * @param UserQueries $userQueries Database query handler for user operations
     */
    public function __construct(UserQueries $userQueries)
    {
        $this->userQueries = $userQueries;
    }

    /**
     * Execute user retrieval process.
     *
     * - Validates `user_id` parameter from the request
     * - Fetches corresponding user data from the database
     * - Ensures successful query execution and existence of the user
     *
     * @param Request $req Request object containing input data
     *
     * @throws InvalidArgumentException If `user_id` is missing or invalid
     * @throws RuntimeException         If the query fails or user not found
     *
     * @return array<string, string> Associative array with 'username' and 'email' fields
     */
    public function execute(Request $req): array
    {
        // Retrieve user ID from authenticated session
        $userId = RequestValidator::getAuthUserId($req);

        // Attempt to retrieve user from database
        $result = $this->userQueries->getUserById($userId);
        RequestValidator::ensureSuccess($result, 'fetch user');

        // Handle case when user is not found
        if (!$result->data) {
            throw new RuntimeException("User not found.");
        }

        $user = $result->data;
        if (
            !is_array($user) || !isset($user['username'], $user['email'])
            || !is_string($user['username']) || !is_string($user['email'])
        ) {
            throw new RuntimeException("Invalid user data returned from getUserById.");
        }

        // Return selected fields
        return [
            'username' => $user['username'],
            'email' => $user['email'],
        ];
    }
}
