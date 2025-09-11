<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\UserQueries;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class UpdateUserService
 *
 * Service responsible for updating an existing user’s information.
 *
 * This service:
 * - Validates provided user ID, username, and email.
 * - Ensures the new username or email is not already taken by another user.
 * - Updates the user record in the database.
 * - Returns the updated username and email.
 *
 * @package App\Api\Auth\Service
 */
class UpdateUserService
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
     * Update user information.
     *
     * Process:
     * - Validate inputs (user_id, username, email).
     * - Check if username or email already exists.
     * - Update the user record in the database.
     *
     * @param array $input Input array containing 'user_id', 'username', and 'email'.
     *
     * @throws InvalidArgumentException If required fields are missing or invalid.
     * @throws RuntimeException         If database operations fail.
     *
     * @return array Updated user data with 'username' and 'email'.
     */
    public function execute(array $input): array
    {
        // Validate user ID
        $userId = trim(strip_tags($input['user_id'] ?? ''));
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }

        // Validate username and email
        $username = trim(strip_tags($input['username'] ?? ''));
        $email = trim(strip_tags($input['email'] ?? ''));

        if ($username === '') {
            throw new InvalidArgumentException('Username is required.');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required.');
        }

        // Ensure username or email is not already taken
        $existsResult = $this->userQueries->checkUserExists($username, $email);
        if (!$existsResult->success) {
            $errorInfo = $existsResult->error ? implode(' | ', $existsResult->error) : 'Unknown error';
            throw new RuntimeException("Failed to check user existence: $errorInfo");
        }
        if ($existsResult->data === true) {
            throw new RuntimeException("Username or email already exists.");
        }

        // Update user in database
        $result = $this->userQueries->updateUser($userId, $username, $email);

        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to update user: $errorInfo");
        }

        // Return updated user data
        return [
            'username' => $result->data['username'],
            'email' => $result->data['email'],
        ];
    }
}
