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

/**
 * Class SigninService
 *
 * Service responsible for handling user sign-in and authentication logic.
 *
 * - Validates `username` and `password` from request
 * - Fetches user from database
 * - Verifies password using PHP's `password_verify`
 * - Generates JWT token for authenticated user
 * - Stores token in cookie for session management
 *
 * @package App\Api\Auth\Service
 */
class SigninService
{
    /** @var UserQueries Handles database queries for user authentication */
    private UserQueries $userQueries;

    /** @var CookieManager Manages authentication cookies */
    private CookieManager $cookieManager;

    /** @var JwtService Service for generating and validating JWT tokens */
    private JwtService $jwt;

    /**
     * Constructor
     *
     * Initializes dependencies for user authentication service.
     /**
     * @param UserQueries         $userQueries       Service for user database queries.
     * @param CookieManager       $cookieManager     Service for managing cookies.
     * @param JwtService          $jwt               Service for JWT operations.
     * @param RefreshTokenService $refreshTokenService Service for refresh token operations.
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
     * Execute user authentication process.
     *
     * Steps:
     * - Validate required parameters (`username`, `password`)
     * - Fetch user record from database
     * - Verify credentials using password hashing
     * - Generate JWT token for the authenticated user
     * - Store access token in secure cookie
     *
     * @param Request $req Request object containing input data
     *
     * @throws InvalidArgumentException If credentials are missing or invalid
     * @throws RuntimeException         If a database or token operation fails
     *
     * @return void
     */
    public function execute(Request $req): void
    {
        $username = RequestValidator::getString($req, 'username', 'Username is required.');
        $password = RequestValidator::getString($req, 'password', 'Password is required.');

        // Fetch user data by username
        $result = $this->userQueries->getUserByName($username);
        RequestValidator::ensureSuccess($result, 'fetch user', false, true);

        if (!$result->hasData() || !$result->data) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        $user = $result->data;
        if (
            !is_array($user) || !isset($user['id'], $user['password'])
            || !is_string($user['id']) || !is_string($user['password'])
        ) {
            throw new RuntimeException('Invalid user data returned from getUserByName.');
        }

        if (!password_verify($password, $user['password'])) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        // Generate JWT token
        $token = $this->jwt->create(['id' => $user['id']]);

        // Set authentication token as cookie (1-hour expiry)
        $this->cookieManager->setAccessToken($token, time() + 3600);

        // Generate and set Refresh Token (7 days)
        $refreshToken = $this->refreshTokenService->create($user['id'], 604800);
        $this->cookieManager->setRefreshToken($refreshToken, time() + 604800);
    }
}
