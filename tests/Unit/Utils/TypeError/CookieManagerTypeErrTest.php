<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\TypeError;

use PHPUnit\Framework\TestCase;
use App\Utils\CookieManager;
use App\Utils\CookieStorageInterface;

/**
 * Class CookieManagerTypeErrTest
 *
 * Unit tests for CookieManager to ensure type safety.
 * Specifically tests that TypeErrors are thrown when invalid types are passed.
 *
 * @package Tests\Unit\Utils\TypeError
 */
class CookieManagerTypeErrTest extends TestCase
{
    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private CookieManager $cookieManager;

    /**
     * @var CookieStorageInterface|\PHPUnit\Framework\MockObject\MockObject Mocked storage for CookieManager.
     */
    private $storageMock;

    /**
     * Set up the test environment before each test.
     *
     * Creates a mock storage and a CookieManager instance with setCookie mocked.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create a mock for CookieStorageInterface
        $this->storageMock = $this->createMock(CookieStorageInterface::class);

        // Create a CookieManager instance using the mock storage,
        // but override the setCookie method so we can track calls without actually setting cookies
        $this->cookieManager = $this->getMockBuilder(CookieManager::class)
            ->setConstructorArgs([$this->storageMock])
            ->onlyMethods(['setCookie'])
            ->getMock();
    }

    /**
     * Test that setAccessToken throws TypeError when given wrong types.
     *
     * @return void
     */
    public function testSetAccessTokenThrowsTypeErrorOnWrongTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the token as an int instead of a string will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->cookieManager->setAccessToken(12345, time() + 3600);
    }

    /**
     * Test that setAccessToken throws TypeError when expiration is not an int.
     *
     * @return void
     */
    public function testSetAccessTokenThrowsTypeErrorOnNonIntExpiry(): void
    {
        $this->expectException(\TypeError::class);

        // Sending expires as a string instead of an int will cause a TypeError.
        /** @phpstan-ignore-next-line */
        $this->cookieManager->setAccessToken('token', 'invalid-expiry');
    }

    /**
     * Test that clearAccessToken works without throwing TypeError.
     *
     * @return void
     */
    public function testClearAccessTokenDoesNotThrow(): void
    {
        $calls = [];

        $this->cookieManager->method('setCookie')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        // Calling a normal function should not cause a TypeError.
        $this->cookieManager->clearAccessToken();

        $this->assertCount(1, $calls);
        $this->assertSame('access_token', $calls[0][0]);
    }
}
