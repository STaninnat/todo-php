<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SignoutService;
use App\Utils\CookieManager;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Api\Helper\TestCookieStorage;

/**
 * Class SignoutServiceIntegrationTest
 *
 * Integration tests for the SignoutService class.
 *
 * This suite verifies:
 * - Access token cookie is cleared upon signout
 * - Signout is idempotent (works even when cookie does not exist)
 *
 * @package Tests\Integration\Api\Auth\Service
 */
class SignoutServiceIntegrationTest extends TestCase
{
    /**
     * @var CookieManager Cookie manager instance for inspecting clear-cookie behavior.
     */
    private CookieManager $cookieManager;

    /**
     * @var SignoutService Service under test.
     */
    private SignoutService $service;

    /**
     * @var TestCookieStorage Cookie storage used for testing set/delete operations.
     */
    private TestCookieStorage $storage;

    /**
     * Setup test environment.
     *
     * Initializes cookie storage, cookie manager, and SignoutService instance.
     * Pre-sets an access_token cookie to simulate an authenticated session.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($this->storage);
        $this->service = new SignoutService($this->cookieManager);

        // Pre-set cookie to simulate existing session
        $this->storage->set('access_token', 'dummy_token', time() + 3600);
    }

    /**
     * Test successful clearing of access token cookie.
     *
     * Ensures:
     * - CookieManager reports correct last cleared cookie
     * - access_token is removed from storage
     *
     * @return void
     */
    public function testExecuteClearsAccessTokenCookie(): void
    {
        $this->service->execute();

        // Confirm the last cookie cleared is 'access_token'
        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());

        // Confirm token removal in storage
        $this->assertNull($this->storage->get('access_token'));
    }

    /**
     * Test behavior when cookie does not exist beforehand.
     *
     * Ensures signout behaves idempotently and does not fail.
     *
     * @return void
     */
    public function testExecuteWhenCookieAlreadyMissing(): void
    {
        // Remove cookie to simulate missing access token
        $this->storage->delete('access_token');

        $this->service->execute();

        // Still expected to have no cookie after execution
        $this->assertNull($this->storage->get('access_token'));
    }
}
