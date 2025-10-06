<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

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
     * @param Request $req Request object containing input data.
     *
     * @throws InvalidArgumentException If required fields are missing or invalid.
     * @throws RuntimeException         If database operations fail.
     *
     * @return array Updated user data with 'username' and 'email'.
     */
    public function execute(Request $req): array
    {
        $userId   = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');
        $username = RequestValidator::getStringParam($req, 'username', 'Username is required.');
        $email    = RequestValidator::getEmailParam($req, 'email', 'Valid email is required.');

        // Check for existing username/email
        $existsResult = $this->userQueries->checkUserExists($username, $email);
        RequestValidator::ensureSuccess($existsResult, 'check user existence');

        if ($existsResult->data === true) {
            throw new RuntimeException("Username or email already exists.");
        }

        // Update user
        $result = $this->userQueries->updateUser($userId, $username, $email);
        RequestValidator::ensureSuccess($result, 'update user');

        return [
            'username' => $result->data['username'],
            'email' => $result->data['email'],
        ];
    }
}
