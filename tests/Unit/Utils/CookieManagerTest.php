<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\CookieManager;
use App\Utils\CookieStorageInterface;

/**
 * Class CookieManagerTest
 *
 * Unit tests for the CookieManager class.
 *
 * This test suite verifies the behavior of CookieManager methods related to:
 * - Getting the 'access_token' cookie
 * - Setting the 'access_token' cookie
 * - Clearing the 'access_token' cookie
 *
 * @package Tests\Unit\Utils
 */
class CookieManagerTest extends TestCase
{
    private $cookieManager;
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
     * Test that getAccessToken returns null if the cookie is not set.
     * 
     * @return void
     */
    public function testGetAccessTokenReturnsNullWhenNotSet(): void
    {
        // Configure the storage mock to return null for 'access_token'
        $this->storageMock->method('get')->with('access_token')->willReturn(null);

        // Assert that CookieManager returns null
        $this->assertNull($this->cookieManager->getAccessToken());
    }

    /**
     * Test that getAccessToken returns the correct token if set.
     * 
     * @return void
     */
    public function testGetAccessTokenReturnsValidToken(): void
    {
        // Configure the storage mock to return 'abc123' for 'access_token'
        $this->storageMock->method('get')->with('access_token')->willReturn('abc123');

        // Assert that CookieManager returns the same token
        $this->assertSame('abc123', $this->cookieManager->getAccessToken());
    }

    /**
     * Test that setAccessToken calls setCookie with correct arguments.
     * 
     * @return void
     */
    public function testSetAccessTokenCallsSetCookieWithCorrectArgs(): void
    {
        $token = 'xyz-token';
        $expires = time() + 3600;
        $calls = []; // array to track calls to setCookie

        // Override setCookie to capture arguments instead of actually setting cookies
        $this->cookieManager->method('setCookie')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        // Call setAccessToken, which should internally call setCookie
        $this->cookieManager->setAccessToken($token, $expires);

        // Assert that setCookie was called once with the correct arguments
        $this->assertCount(1, $calls);
        $this->assertSame(['access_token', $token, $expires], $calls[0]);
    }

    /**
     * Test that clearAccessToken calls setCookie with an expired timestamp.
     * 
     * @return void
     */
    public function testClearAccessTokenCallsSetCookieWithExpiredTime(): void
    {
        $calls = [];

        // Override setCookie to capture arguments
        $this->cookieManager->method('setCookie')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        // Call clearAccessToken, which should set an expired cookie
        $this->cookieManager->clearAccessToken();

        // Assert that setCookie was called once with 'access_token' and empty value
        $this->assertCount(1, $calls);
        $this->assertSame('access_token', $calls[0][0]);
        $this->assertSame('', $calls[0][1]);
        $this->assertLessThanOrEqual(time() - 3600, $calls[0][2]); // Ensure expiration is in the past
    }

    /**
     * Test that setting and then clearing the access token produces the correct call order.
     * 
     * @return void
     */
    public function testSetThenClearAccessTokenOrder(): void
    {
        $token = 'new-token';
        $expires = time() + 3600;
        $calls = [];

        // Capture calls to setCookie
        $this->cookieManager->method('setCookie')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        // First set the token, then clear it
        $this->cookieManager->setAccessToken($token, $expires);
        $this->cookieManager->clearAccessToken();

        // Assert that two calls were made in correct order
        $this->assertCount(2, $calls);

        // First call: set token
        $this->assertSame(['access_token', $token, $expires], $calls[0]);

        // Second call: clear token
        $this->assertSame('access_token', $calls[1][0]);
        $this->assertSame('', $calls[1][1]);
        $this->assertLessThanOrEqual(time() - 3600, $calls[1][2]);
    }

    /**
     * Test that multiple calls to setAccessToken overwrite the previous values correctly.
     * 
     * @return void
     */
    public function testMultipleSetAccessTokenOverwritesEachTime(): void
    {
        $tokens = ['first-token', 'second-token', 'third-token'];
        $expires = time() + 3600;
        $calls = [];

        // Capture calls to setCookie
        $this->cookieManager->method('setCookie')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        // Call setAccessToken multiple times
        foreach ($tokens as $token) {
            $this->cookieManager->setAccessToken($token, $expires);
        }

        // Assert that three calls were made and each token matches
        $this->assertCount(3, $calls);
        foreach ($tokens as $i => $token) {
            $this->assertSame(['access_token', $token, $expires], $calls[$i]);
        }
    }
}
