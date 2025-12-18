<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\SignoutService;
use App\Utils\RefreshTokenService;
use App\Utils\CookieManager;

/**
 * Class SignoutServiceTest
 *
 * Unit tests for SignoutService.
 *
 * This test suite verifies that signout correctly clears the access token via CookieManager.
 *
 * @package Tests\Unit\Api\Auth\Service
 */
class SignoutServiceUnitTest extends TestCase
{
    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /** @var RefreshTokenService&\PHPUnit\Framework\MockObject\MockObject */
    private RefreshTokenService $refreshTokenService;

    private SignoutService $service;

    /**
     * Setup mock and service instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock for CookieManager
        $this->cookieManager = $this->createMock(CookieManager::class);

        // Create mock for RefreshTokenService
        $this->refreshTokenService = $this->createMock(RefreshTokenService::class);

        // Instantiate service with mock
        $this->service = new SignoutService($this->cookieManager, $this->refreshTokenService);
    }

    /**
     * Test that execute() calls clearAccessToken() on CookieManager.
     *
     * @return void
     */
    public function testExecuteCallsClearAccessToken(): void
    {
        // Expect clearAccessToken() to be called once
        $this->cookieManager->expects($this->once())
            ->method('clearAccessToken');

        // Expect clearRefreshToken() to be called once
        $this->cookieManager->expects($this->once())
            ->method('clearRefreshToken');

        // Act: call the service
        $this->service->execute();
    }
    /**
     * Test that execute() revokes refresh token if one exists in cookie.
     *
     * @return void
     */
    public function testExecuteRevokesRefreshTokenIfPresent(): void
    {
        $this->cookieManager->method('getRefreshToken')->willReturn('some_refresh_token');

        $this->refreshTokenService->expects($this->once())
            ->method('revoke')
            ->with('some_refresh_token');

        $this->service->execute();
    }
}
