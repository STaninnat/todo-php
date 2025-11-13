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
 * This suite verifies that:
 * - setAccessToken throws TypeError for invalid token type
 * - setAccessToken throws TypeError for non-int expiration
 * - clearAccessToken works correctly without throwing
 *
 * @package Tests\Unit\Utils\TypeError
 */
class CookieManagerTypeErrTest extends TestCase
{
    /** @var CookieManager CookieManager instance with mock storage */
    private CookieManager $cookieManager;

    /** @var CookieStorageInterface Mocked storage for CookieManager */
    private CookieStorageInterface $storageMock;

    /**
     * Set up the test environment before each test.
     *
     * Creates a mock storage and a CookieManager instance.
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
     * Test that setAccessToken throws TypeError when token is not a string.
     *
     * @return void
     */
    public function testSetAccessTokenThrowsTypeErrorOnWrongTypes(): void
    {
        $this->expectException(\TypeError::class);

        // Sending the token as an int instead of a string should throw a TypeError
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

        // Sending expires as a string instead of int should throw a TypeError
        $this->cookieManager->setAccessToken('token', 'invalid-expiry');
    }

    /**
     * Test that clearAccessToken does not throw any exception and updates lastSetCookieName.
     *
     * @return void
     */
    public function testClearAccessTokenDoesNotThrow(): void
    {
        // Should not throw; lastSetCookieName should still be updated
        $this->cookieManager->clearAccessToken();

        // Verify lastSetCookieName updated correctly
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());
    }
}
