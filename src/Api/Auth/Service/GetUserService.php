<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

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
     * @param Request $req Request object containing input data.
     *
     * @throws InvalidArgumentException If user_id is missing or empty.
     * @throws RuntimeException         If fetching the user fails or the user does not exist.
     *
     * @return array Associative array with 'username' and 'email'.
     */
    public function execute(Request $req): array
    {
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');

        // Attempt to retrieve user from database
        $result = $this->userQueries->getUserById($userId);
        RequestValidator::ensureSuccess($result, 'fetch user');

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
