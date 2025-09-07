<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\CookieManager;
use App\Utils\CookieStorageInterface;

class CookieManagerTypeErrTest extends TestCase
{
    private $cookieManager;
    private $storageMock;

    /**
     * Set up the test environment before each test.
     * Creates a mock storage and a CookieManager instance with setCookie mocked.
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
     */
    public function testSetAccessTokenThrowsTypeErrorOnWrongTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the token as an int instead of a string will cause a TypeError.
        $this->cookieManager->setAccessToken(12345, time() + 3600);
    }

    /**
     * Test that setAccessToken throws TypeError when expiration is not int.
     */
    public function testSetAccessTokenThrowsTypeErrorOnNonIntExpiry(): void
    {
        $this->expectException(\TypeError::class);

        // Sending expires as a string instead of an int will cause a TypeError.
        $this->cookieManager->setAccessToken('token', 'invalid-expiry');
    }

    /**
     * Test that clearAccessToken still works (just to confirm no type issues).
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
