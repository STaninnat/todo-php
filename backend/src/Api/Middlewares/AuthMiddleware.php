<?php

declare(strict_types=1);

namespace App\Api\Middlewares;

use App\Api\Request;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\Api\Exceptions\UnauthorizedException;
use RuntimeException;

/**
 * Class AuthMiddleware
 *
 * Middleware responsible for handling authentication via JWT.
 *
 * This middleware provides:
 * - refreshJwt(): Verifies JWT tokens, refreshes them when required,
 *   and attaches the decoded payload to the Request.
 * - requireAuth(): Ensures that a request is authenticated,
 *   throwing a RuntimeException if not.
 *
 * @package App\Api\Middlewares
 */
class AuthMiddleware
{
    private CookieManager $cookieManager;
    private JwtService $jwt;

    /**
     * Constructor
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
     * Process:
     * - Retrieve current access token from cookies.
     * - Verify token and decode payload.
     * - Attach payload to the Request object.
     * - Refresh token and update cookies if near expiration.
     *
     * @param Request $req The incoming request instance, enriched with authentication data.
     * @param int|null $now Optional current timestamp (for testing or override); defaults to `time()`.
     *
     * @return void
     */
    public function refreshJwt(Request $req, ?int $now = null): void
    {
        $now = $now ?? time();

        // Retrieve current access token from cookies
        $token = $this->cookieManager->getAccessToken();

        // Verify token and get its payload (returns null if invalid)
        $payload = $this->jwt->verify($token);

        if ($payload) {
            // Attach decoded JWT payload to the request for later use
            $req->auth = $payload;

            // Refresh token if it's close to expiration
            if ($this->jwt->shouldRefresh($payload, $now)) {
                $newToken = $this->jwt->refresh($payload, $now);

                // Save refreshed token in cookies (valid for 1 hour)
                $this->cookieManager->setAccessToken($newToken, $now + 3600);
            }
        }
    }

    /**
     * Ensure that the request is authenticated.
     *
     * @param Request $req The incoming request that should contain an auth payload.
     *
     * @throws RuntimeException If the request is not authenticated.
     *
     * @return void
     */
    public function requireAuth(Request $req): void
    {
        // If no authentication payload is found, reject the request
        if (!$req->auth) {
            throw new UnauthorizedException('Unauthorized. You must be logged in.');
        }
    }
}
