<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class SigninService
 *
 * Service responsible for authenticating a user.
 *
 * This service:
 * - Validates username and password inputs.
 * - Fetches user data from the database by username.
 * - Verifies the provided password against the stored hash.
 * - Generates a JWT token upon successful authentication.
 * - Stores the token in cookies for session management.
 *
 * @package App\Api\Auth\Service
 */
class SigninService
{
    private UserQueries $userQueries;
    private CookieManager $cookieManager;
    private JwtService $jwt;

    /**
     * Constructor
     *
     * @param UserQueries   $userQueries   Database query handler for user operations.
     * @param CookieManager $cookieManager Utility for managing authentication cookies.
     * @param JwtService    $jwt           Service for generating and validating JWT tokens.
     */
    public function __construct(UserQueries $userQueries, CookieManager $cookieManager, JwtService $jwt)
    {
        $this->userQueries = $userQueries;
        $this->cookieManager = $cookieManager;
        $this->jwt = $jwt;
    }

    /**
     * Authenticate a user using username and password.
     *
     * Process:
     * - Validate input credentials.
     * - Retrieve user data by username.
     * - Verify the provided password.
     * - Generate a JWT token if authentication succeeds.
     * - Store the token in cookies (valid for 1 hour).
     *
     * @param array $input Input array containing 'username' and 'password'.
     *
     * @throws InvalidArgumentException If required fields are missing or credentials are invalid.
     * @throws RuntimeException         If database operations fail.
     *
     * @return void
     */
    public function execute(array $input): void
    {
        // Sanitize and validate username
        $username = trim(strip_tags($input['username'] ?? ''));
        $password = trim(strip_tags($input['password'] ?? ''));

        if ($username === '') {
            throw new InvalidArgumentException('Username is required.');
        }
        if ($password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        // Retrieve user by username
        $result = $this->userQueries->getUserByName($username);
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to fetch user: $errorInfo");
        }
        if (!$result->hasData() || !$result->data) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        $user = $result->data;

        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        // Create JWT token
        $token = $this->jwt->create(['id' => $user['id']]);

        // Store JWT token in cookies (1 hour validity)
        $this->cookieManager->setAccessToken($token, time() + 3600);
    }
}
