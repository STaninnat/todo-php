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
     * @param Request $req Request object containing input data.
     *
     * @throws InvalidArgumentException If required fields are missing or credentials are invalid.
     * @throws RuntimeException         If database operations fail.
     *
     * @return void
     */
    public function execute(Request $req): void
    {
        $username = RequestValidator::getStringParam($req, 'username', 'Username is required.');
        $password = RequestValidator::getStringParam($req, 'password', 'Password is required.');

        $result = $this->userQueries->getUserByName($username);
        RequestValidator::ensureSuccess($result, 'fetch user');

        if (!$result->hasData() || !$result->data) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        $user = $result->data;

        if (!password_verify($password, $user['password'])) {
            throw new InvalidArgumentException('Invalid username or password.');
        }

        $token = $this->jwt->create(['id' => $user['id']]);

        $this->cookieManager->setAccessToken($token, time() + 3600);
    }
}
