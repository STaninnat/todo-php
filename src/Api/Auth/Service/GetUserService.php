<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\UserQueries;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class GetUserService
 *
 * Service responsible for retrieving a user's information from the system.
 * 
 * This service:
 * - Validates the provided user ID.
 * - Fetches the user record from the database.
 * - Returns user details (username and email).
 * - Throws exceptions if validation fails, user is not found, or database errors occur.
 *
 * @package App\Api\Auth\Service
 */
class GetUserService
{
    private UserQueries $userQueries;

    /**
     * Constructor
     *
     * @param UserQueries $userQueries Database query handler for user operations.
     */
    public function __construct(UserQueries $userQueries)
    {
        $this->userQueries = $userQueries;
    }

    /**
     * Retrieve a user's information by ID.
     *
     * Process:
     * - Validate input and ensure a valid user ID is provided.
     * - Fetch user data using UserQueries.
     * - Handle errors and missing users appropriately.
     * - Return only selected fields (username, email).
     *
     * @param array $input Input array containing the 'user_id' field.
     *
     * @throws InvalidArgumentException If user_id is missing or empty.
     * @throws RuntimeException         If fetching the user fails or the user does not exist.
     *
     * @return array Associative array with 'username' and 'email'.
     */
    public function execute(array $input): array
    {
        // Sanitize and validate user_id
        $userId = trim(strip_tags($input['user_id'] ?? ''));
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }

        // Attempt to retrieve user from database
        $result = $this->userQueries->getUserById($userId);

        // Handle query failure
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to fetch user: $errorInfo");
        }

        // Handle case when user is not found
        if (!$result->data) {
            throw new RuntimeException("User not found.");
        }

        // Return selected fields
        return [
            'username' => $result->data['username'],
            'email' => $result->data['email'],
        ];
    }
}
