<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use RuntimeException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Class SignupService
 *
 * Service responsible for registering a new user.
 *
 * This service:
 * - Validates username, email, and password inputs.
 * - Ensures the username or email does not already exist.
 * - Creates a new user in the database with a hashed password.
 * - Generates a JWT token upon successful signup.
 * - Stores the token in cookies for session management.
 *
 * @package App\Api\Auth\Service
 */
class SignupService
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
     * Register a new user.
     *
     * Process:
     * - Validate input (username, email, password).
     * - Check if username or email already exists.
     * - Create a new user with UUID and hashed password.
     * - Generate a JWT token for the new user.
     * - Store the token in cookies (valid for 1 hour).
     *
     * @param array $input Input array containing 'username', 'email', and 'password'.
     *
     * @throws InvalidArgumentException If required fields are missing, invalid, or already exist.
     * @throws RuntimeException         If database operations fail.
     *
     * @return void
     */
    public function execute(array $input): void
    {
        // Sanitize and validate inputs
        $username = trim(strip_tags($input['username'] ?? ''));
        $email = trim(strip_tags($input['email'] ?? ''));
        $password = trim(strip_tags($input['password'] ?? ''));

        if ($username === '') {
            throw new InvalidArgumentException('Username is required.');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required.');
        }
        if ($password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        // Check if user already exists
        $existsResult = $this->userQueries->checkUserExists($username, $email);
        if (!$existsResult->success) {
            $errorInfo = $existsResult->error ? implode(' | ', $existsResult->error) : 'Unknown error';
            throw new RuntimeException("Failed to check user existence: $errorInfo");
        }
        if ($existsResult->data === true) {
            throw new InvalidArgumentException("Username or email already exists.");
        }

        // Generate new user ID and hash password
        $id = Uuid::uuid4()->toString();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create user in database
        $result = $this->userQueries->createUser($id, $username, $email, $hashedPassword);
        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to sign up: $errorInfo");
        }

        $user = $result->data;

        // Create JWT token for the new user
        $token = $this->jwt->create(['id' => $user['id']]);

        // Store token in cookies (1 hour validity)
        $this->cookieManager->setAccessToken($token, time() + 3600);
    }
}
