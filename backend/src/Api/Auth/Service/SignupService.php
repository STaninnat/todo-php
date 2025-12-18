<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Api\Request;
use App\DB\UserQueries;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Class SignupService
 *
 * Handles the logic for registering new users.
 *
 * Responsibilities:
 * - Validate registration input
 * - Check if username or email already exists
 * - Hash password securely
 * - Create user record in database
 * - Generate JWT token and store it as an access cookie
 *
 * @package App\Api\Auth\Service
 */
class SignupService
{
    /** @var UserQueries Handles user-related database operations */
    private UserQueries $userQueries;

    /** @var CookieManager Manages authentication cookies */
    private CookieManager $cookieManager;

    /** @var JwtService Handles creation and validation of JWT tokens */
    private JwtService $jwt;


    /**
     * Constructor
     *
     * Initializes dependencies required for user registration.
     /**
     * @param UserQueries         $userQueries         Database query handler for user operations
     * @param CookieManager       $cookieManager       Utility for managing authentication cookies
     * @param JwtService          $jwt                 Service for generating and validating JWT tokens
     * @param RefreshTokenService $refreshTokenService Service for handling refresh tokens
     */
    public function __construct(
        UserQueries $userQueries,
        CookieManager $cookieManager,
        JwtService $jwt,
        private RefreshTokenService $refreshTokenService
    ) {
        $this->userQueries = $userQueries;
        $this->cookieManager = $cookieManager;
        $this->jwt = $jwt;
    }

    /**
     * Execute user signup process.
     *
     * - Validates input fields (`username`, `email`, `password`)
     * - Checks for duplicate user or email
     * - Hashes password using PHP's secure algorithm
     * - Inserts new user record into the database
     * - Generates and sets an access token cookie
     * - Generates and sets a refresh token cookie
     *
     * @param Request $req Request object containing input data
     *
     * @throws InvalidArgumentException If required fields are missing, invalid, or already exist
     * @throws RuntimeException         If any database operation fails
     *
     * @return array The newly created user data.
     */
    public function execute(Request $req): array
    {
        $username = RequestValidator::getString($req, 'username', 'Username is required.');
        $email = RequestValidator::getEmail($req, 'email', 'Valid email is required.');
        $password = RequestValidator::getString($req, 'password', 'Password is required.');

        // Ensure username or email does not already exist
        $existsResult = $this->userQueries->checkUserExists($username, $email);
        RequestValidator::ensureSuccess($existsResult, 'check user existence', false, true);

        if ($existsResult->data === true) {
            throw new InvalidArgumentException("Username or email already exists.");
        }

        if (strlen($username) > 255) {
            throw new RuntimeException('Username too long');
        }

        // Generate unique ID and securely hash password
        $id = Uuid::uuid4()->toString();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create new user record in database
        $result = $this->userQueries->createUser($id, $username, $email, $hashedPassword);
        RequestValidator::ensureSuccess($result, 'sign up');

        // Retrieve created user and issue JWT access token
        $user = $result->data;
        if (!is_array($user) || !isset($user['id']) || !is_string($user['id'])) {
            throw new RuntimeException('Invalid user data returned from createUser.');
        }

        // Access Token (1 hour)
        $token = $this->jwt->create(['id' => $user['id']]);
        $this->cookieManager->setAccessToken($token, time() + 3600);

        // Refresh Token (7 days)
        $refreshToken = $this->refreshTokenService->create($user['id'], 604800);
        $this->cookieManager->setRefreshToken($refreshToken, time() + 604800);

        // Remove sensitive data
        unset($user['password']);

        return $user;
    }
}
