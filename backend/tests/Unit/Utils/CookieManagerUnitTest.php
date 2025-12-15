<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\CookieManager;
use App\Utils\CookieStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;

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
class CookieManagerUnitTest extends TestCase
{
    /** @var CookieManager CookieManager instance using a mock storage */
    private CookieManager $cookieManager;

    /** @var CookieStorageInterface&MockObject Mocked storage for CookieManager */
    private CookieStorageInterface $storageMock;

    /**
     * Set up the test environment before each test.
     *
     * Creates a mock storage and a CookieManager instance with the mock injected.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create a mock for CookieStorageInterface
        $this->storageMock = $this->createMock(CookieStorageInterface::class);

        // Create a CookieManager instance using the mock storage
        $this->cookieManager = new CookieManager($this->storageMock);
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
        // Configure the storage mock to return a valid token
        $this->storageMock->method('get')->with('access_token')->willReturn('abc123');

        // Assert that CookieManager returns the same token
        $this->assertSame('abc123', $this->cookieManager->getAccessToken());
    }


    /**
     * Test that setAccessToken calls storage set method with correct arguments.
     * 
     * @return void
     */
    public function testSetAccessTokenCallsStorageSet(): void
    {
        $token = 'xyz-token';
        $expires = time() + 3600;

        // Expect the storage set method to be called once with correct args
        $this->storageMock->expects($this->once())
            ->method('set')
            ->with('access_token', $token, $expires);

        $this->cookieManager->setAccessToken($token, $expires);

        // Verify lastSetCookieName updated
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    /**
     * Test that clearAccessToken calls storage delete method.
     * 
     * @return void
     */
    public function testClearAccessTokenCallsStorageDelete(): void
    {
        $this->storageMock->expects($this->once())
            ->method('delete')
            ->with('access_token');

        $this->cookieManager->clearAccessToken();

        // Verify lastSetCookieName updated
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    /**
     * Test the order of setAccessToken followed by clearAccessToken calls.
     * 
     * Ensures that set is called first, then delete.
     * 
     * @return void
     */
    public function testSetThenClearAccessTokenOrder(): void
    {
        $token = 'new-token';
        $expires = time() + 3600;
        $calls = [];

        // Capture calls to set
        $this->storageMock->method('set')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = ['set', $name, $value, $exp]; // inline comment: track set calls
            });

        // Capture calls to delete
        $this->storageMock->method('delete')
            ->willReturnCallback(function ($name) use (&$calls) {
                $calls[] = ['delete', $name]; // inline comment: track delete calls
            });

        $this->cookieManager->setAccessToken($token, $expires);
        $this->cookieManager->clearAccessToken();

        // Ensure exactly 2 calls were made
        $this->assertCount(2, $calls);

        // Verify call order and arguments
        $this->assertSame(['set', 'access_token', $token, $expires], $calls[0]);
        $this->assertSame(['delete', 'access_token'], $calls[1]);
    }

    /**
     * Test that multiple calls to setAccessToken overwrite previous values correctly.
     * 
     * @return void
     */
    public function testMultipleSetAccessTokenOverwritesEachTime(): void
    {
        $tokens = ['first-token', 'second-token', 'third-token'];
        $expires = time() + 3600;
        $calls = [];

        // Capture all set calls
        $this->storageMock->method('set')
            ->willReturnCallback(function ($name, $value, $exp) use (&$calls) {
                $calls[] = [$name, $value, $exp];
            });

        foreach ($tokens as $token) {
            $this->cookieManager->setAccessToken($token, $expires);
        }

        // Ensure call count matches number of tokens
        $this->assertCount(count($tokens), $calls);

        // Verify each call matches expected token
        foreach ($tokens as $i => $token) {
            $this->assertSame(['access_token', $token, $expires], $calls[$i]);
        }
    }

    /**
     * Test that clearAccessToken does not throw and updates lastSetCookieName.
     * 
     * @return void
     */
    public function testClearAccessTokenDoesNotThrow(): void
    {
        // Call delete (no expectation, just ensure no exception)
        $this->storageMock->method('delete')->with('access_token');

        $this->cookieManager->clearAccessToken();

        // Verify lastSetCookieName updated
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }

    /**
     * Test that getRefreshToken returns correct token when set.
     *
     * @return void
     */
    public function testGetRefreshTokenReturnsValue(): void
    {
        $this->storageMock->method('get')->with('refresh_token')->willReturn('ref-abc');
        $this->assertSame('ref-abc', $this->cookieManager->getRefreshToken());
    }

    /**
     * Test that setRefreshToken calls storage set with correct args.
     *
     * @return void
     */
    public function testSetRefreshTokenCallsStorage(): void
    {
        $token = 'ref-xyz';
        $expires = time() + 5000;

        $this->storageMock->expects($this->once())
            ->method('set')
            ->with('refresh_token', $token, $expires);

        $this->cookieManager->setRefreshToken($token, $expires);
        $this->assertSame('refresh_token', $this->cookieManager->getLastSetCookieName());
    }

    /**
     * Test that clearRefreshToken calls storage delete.
     *
     * @return void
     */
    public function testClearRefreshTokenCallsDelete(): void
    {
        $this->storageMock->expects($this->once())
            ->method('delete')
            ->with('refresh_token');

        $this->cookieManager->clearRefreshToken();
        $this->assertSame('refresh_token', $this->cookieManager->getLastSetCookieName());
    }
}
