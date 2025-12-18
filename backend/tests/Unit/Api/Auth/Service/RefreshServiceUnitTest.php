<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\RefreshService;
use App\Utils\RefreshTokenService;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use RuntimeException;

/**
 * Class RefreshServiceUnitTest
 *
 * Unit tests for {@see RefreshService}.
 *
 * Verifies business logic for token refresh flow using mocks.
 *
 * @package Tests\Unit\Auth
 */
class RefreshServiceUnitTest extends TestCase
{
    /** @var RefreshTokenService&\PHPUnit\Framework\MockObject\MockObject Mock for token persistence */
    private $refreshTokenService;

    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject Mock for cookie handling */
    private $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject Mock for JWT generation */
    private $jwt;

    /** @var RefreshService Service under test */
    private RefreshService $service;

    /**
     * Set up test environment with mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->refreshTokenService = $this->createMock(RefreshTokenService::class);
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new RefreshService(
            $this->refreshTokenService,
            $this->cookieManager,
            $this->jwt
        );
    }

    /**
     * Test logic throws exception when no refresh token is present in cookies.
     *
     * @return void
     */
    public function testExecuteThrowsIfNoRefreshToken(): void
    {
        $this->cookieManager->method('getRefreshToken')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refresh token missing');

        $this->service->execute();
    }

    /**
     * Test successful execution flow.
     *
     * Verifies:
     * - Refresh token retrieval
     * - Refresh token verification
     * - Old token revocation
     * - New access token generation and setting
     * - New refresh token generation and setting
     *
     * @return void
     */
    public function testExecuteSuccessFlow(): void
    {
        $oldToken = 'old_refresh_token';
        $newToken = 'new_refresh_token';
        $accessToken = 'new_access_token';
        $userId = 'user123';

        // 1. Get token from cookie
        $this->cookieManager->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn($oldToken);

        // 2. Verify token
        $this->refreshTokenService->expects($this->once())
            ->method('verify')
            ->with($oldToken)
            ->willReturn($userId);

        // 3. Revoke old token
        $this->refreshTokenService->expects($this->once())
            ->method('revoke')
            ->with($oldToken);

        // 4. Create new Access Token
        $this->jwt->expects($this->once())
            ->method('create')
            ->with(['id' => $userId])
            ->willReturn($accessToken);

        // 5. Set Access Token Cookie
        $this->cookieManager->expects($this->once())
            ->method('setAccessToken')
            ->with(
                $accessToken,
                $this->callback(function ($exp) {
                    return $exp > time();
                })
            );

        // 6. Create new Refresh Token
        $this->refreshTokenService->expects($this->once())
            ->method('create')
            ->with($userId, 604800)
            ->willReturn($newToken);

        // 7. Set Refresh Token Cookie
        $this->cookieManager->expects($this->once())
            ->method('setRefreshToken')
            ->with(
                $newToken,
                $this->callback(function ($exp) {
                    return $exp > time();
                })
            );

        $this->service->execute();
    }
}
