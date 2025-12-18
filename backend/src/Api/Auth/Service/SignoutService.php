<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Utils\CookieManager;
use App\Utils\RefreshTokenService;

/**
 * Class SignoutService
 *
 * Service responsible for handling user sign-out.
 *
 * This service:
 * - Clears the authentication token stored in cookies.
 * - Effectively invalidates the current user session.
 *
 * @package App\Api\Auth\Service
 */
class SignoutService
{
    private RefreshTokenService $refreshTokenService;
    private CookieManager $cookieManager;

    /**
     * Constructor
     *
     * @param CookieManager       $cookieManager       Utility for managing authentication cookies.
     * @param RefreshTokenService $refreshTokenService Service for handling refresh tokens.
     */
    public function __construct(CookieManager $cookieManager, RefreshTokenService $refreshTokenService)
    {
        $this->cookieManager = $cookieManager;
        $this->refreshTokenService = $refreshTokenService;
    }

    /**
     * Execute the sign-out process.
     *
     * Process:
     * - Revoke the refresh token if present.
     * - Clear the access & refresh tokens from cookies.
     *
     * @return void
     */
    public function execute(): void
    {
        // 1. Revoke refresh token if exists
        $refreshToken = $this->cookieManager->getRefreshToken();
        if ($refreshToken) {
            try {
                $this->refreshTokenService->revoke($refreshToken);
            } catch (\Exception $e) {
                // Best effort revocation, ignore errors during signout
            }
        }

        // 2. Remove tokens from cookies
        $this->cookieManager->clearAccessToken();
        $this->cookieManager->clearRefreshToken();
    }
}
