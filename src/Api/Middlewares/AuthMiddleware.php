<?php

declare(strict_types=1);

namespace App\Api\Middlewares;

use App\Api\Request;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use RuntimeException;

/**
 * Middleware responsible for handling authentication via JWT.
 * - Refreshes JWT tokens when needed.
 * - Ensures that requests are authenticated before proceeding.
 */
class AuthMiddleware
{
    private CookieManager $cookieManager;
    private JwtService $jwt;

    /**
     * Constructor.
     *
     * @param CookieManager $cookieManager Handles access token storage and retrieval via cookies.
     * @param JwtService    $jwt           Service responsible for verifying and refreshing JWT tokens.
     */
    public function __construct(CookieManager $cookieManager, JwtService $jwt)
    {
        $this->cookieManager = $cookieManager;
        $this->jwt = $jwt;
    }

    /**
     * Refresh JWT token if needed and attach payload to the request.
     *
     * @param Request $req The incoming request instance, which will be enriched with authentication data.
     *
     * @return void
     */
    public function refreshJwt(Request $req): void
    {
        // Retrieve current access token from cookies
        $token = $this->cookieManager->getAccessToken();

        // Verify token and get its payload (returns null if invalid)
        $payload = $this->jwt->verify($token);

        if ($payload) {
            // Attach decoded JWT payload to the request for later use
            $req->auth = $payload;

            // Refresh token if it's close to expiration
            if ($this->jwt->shouldRefresh($payload)) {
                $newToken = $this->jwt->refresh($payload);

                // Save refreshed token in cookies (valid for 1 hour)
                $this->cookieManager->setAccessToken($newToken, time() + 3600);
            }
        }
    }

    /**
     * Ensure that the request is authenticated.
     *
     * @param Request $req The incoming request that should contain an auth payload.
     *
     * @throws RuntimeException If the request is not authenticated.
     * @return void
     */
    public function requireAuth(Request $req): void
    {
        // If no authentication payload is found, reject the request
        if (!$req->auth) {
            throw new RuntimeException('Unauthorized. You must be logged in.');
        }
    }
}
