<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Auth\Service;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\SignoutService;
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

        // Instantiate service with mock
        $this->service = new SignoutService($this->cookieManager);
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

        // Act: call the service
        $this->service->execute();
    }
}
