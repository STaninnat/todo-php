<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class UpdateUserService
 *
 * Handles user information update operations.
 *
 * - Validates and sanitizes input parameters (`user_id`, `username`, `email`)
 * - Checks for duplicate username or email
 * - Updates user record in the database and returns updated information
 *
 * @package App\Api\Auth\Service
 */
class UpdateUserService
{
    /** @var UserQueries Database query handler for user-related operations */
    private UserQueries $userQueries;

    /**
     * Constructor
     *
     * @param UserQueries $userQueries Database query handler for user operations
     */
    public function __construct(UserQueries $userQueries)
    {
        $this->userQueries = $userQueries;
    }

    /**
     * Execute user update process.
     *
     * - Validates request parameters (`user_id`, `username`, `email`)
     * - Checks if username or email already exists
     * - Updates user data in the database
     *
     * @param Request $req Request object containing input data
     *
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws RuntimeException         If the username/email already exists or database update fails
     *
     * @return array<string, string> Updated user data containing 'username' and 'email'
     */
    public function execute(Request $req): array
    {
        // Retrieve user ID from authenticated session
        $userId = RequestValidator::getAuthUserId($req);
        $username = RequestValidator::getString($req, 'username', 'Username is required.');
        $email = RequestValidator::getEmail($req, 'email', 'Valid email is required.');

        // Check for existing username or email to prevent duplication
        $existsResult = $this->userQueries->checkUserExists($username, $email);
        RequestValidator::ensureSuccess($existsResult, 'check user existence', false, true);

        if ($existsResult->data === true) {
            throw new RuntimeException("Username or email already exists.");
        }

        // Perform user update operation
        $result = $this->userQueries->updateUser($userId, $username, $email);
        RequestValidator::ensureSuccess($result, 'update user');

        // Cast data to array to satisfy PHPStan
        $data = (array) $result->data;
        if (!isset($data['username'], $data['email']) || !is_string($data['username']) || !is_string($data['email'])) {
            throw new RuntimeException('Invalid data returned from updateUser.');
        }

        // Return the updated user info for confirmation
        return [
            'username' => $data['username'],
            'email' => $data['email'],
        ];
    }
}
