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
     * @param Request $req Request object containing input data.
     *
     * @throws InvalidArgumentException If required fields are missing, invalid, or already exist.
     * @throws RuntimeException         If database operations fail.
     *
     * @return void
     */
    public function execute(Request $req): void
    {
        $username = RequestValidator::getStringParam($req, 'username', 'Username is required.');
        $email    = RequestValidator::getEmailParam($req, 'email', 'Valid email is required.');
        $password = RequestValidator::getStringParam($req, 'password', 'Password is required.');

        $existsResult = $this->userQueries->checkUserExists($username, $email);
        RequestValidator::ensureSuccess($existsResult, 'check user existence');

        if ($existsResult->data === true) {
            throw new InvalidArgumentException("Username or email already exists.");
        }

        $id = Uuid::uuid4()->toString();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->userQueries->createUser($id, $username, $email, $hashedPassword);
        RequestValidator::ensureSuccess($result, 'sign up');

        $user = $result->data;
        $token = $this->jwt->create(['id' => $user['id']]);

        $this->cookieManager->setAccessToken($token, time() + 3600);
    }
}
