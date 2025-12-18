<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\Utils\RefreshTokenService;
use RuntimeException;

/**
 * Class RefreshService
 *
 * Handles refreshing of access tokens using a valid refresh token.
 *
 * @package App\Api\Auth\Service
 */
class RefreshService
{
    /** @var RefreshTokenService Service for managing refresh token persistence */
    private RefreshTokenService $refreshTokenService;

    /** @var CookieManager Helper for managing HTTP cookies */
    private CookieManager $cookieManager;

    /** @var JwtService Service for handling JWT operations */
    private JwtService $jwt;

    /**
     * RefreshService constructor.
     *
     * @param RefreshTokenService $refreshTokenService Service for token persistence/verification
     * @param CookieManager       $cookieManager       Service for cookie retrieval/setting
     * @param JwtService          $jwt                 Service for JWT creation
     */
    public function __construct(
        RefreshTokenService $refreshTokenService,
        CookieManager $cookieManager,
        JwtService $jwt
    ) {
        $this->refreshTokenService = $refreshTokenService;
        $this->cookieManager = $cookieManager;
        $this->jwt = $jwt;
    }

    /**
     * Execute the refresh token flow.
     *
     * @return void
     * @throws RuntimeException if refresh token is missing or invalid
     */
    public function execute(): void
    {
        $refreshToken = $this->cookieManager->getRefreshToken();

        if (!$refreshToken) {
            throw new RuntimeException("Refresh token missing");
        }

        // Verify and get user ID
        $userId = $this->refreshTokenService->verify($refreshToken);

        // Revoke old refresh token (Reuse Detection / Rotation Policy)
        // Ideally we should do rotation: revoke old, issue new.
        $this->refreshTokenService->revoke($refreshToken);

        // Issue new Access Token (1 hour)
        $newAccessToken = $this->jwt->create(['id' => $userId]);
        $this->cookieManager->setAccessToken($newAccessToken, time() + 3600);

        // Issue new Refresh Token (7 days)
        $newRefreshToken = $this->refreshTokenService->create($userId, 604800);
        $this->cookieManager->setRefreshToken($newRefreshToken, time() + 604800);
    }
}
