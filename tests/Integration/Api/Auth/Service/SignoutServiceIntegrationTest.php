<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Auth\Service;

use App\Api\Auth\Service\SignoutService;
use App\Utils\CookieManager;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Api\Helper\TestCookieStorage;

class SignoutServiceIntegrationTest extends TestCase
{
    private CookieManager $cookieManager;
    private SignoutService $service;
    private TestCookieStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new TestCookieStorage();
        $this->cookieManager = new CookieManager($this->storage);
        $this->service = new SignoutService($this->cookieManager);

        $this->storage->set('access_token', 'dummy_token', time() + 3600);
    }

    public function testExecuteClearsAccessTokenCookie(): void
    {
        $this->service->execute();

        $this->assertSame('access_token', $this->cookieManager->getLastSetCookieName());

        $this->assertNull($this->storage->get('access_token'));
    }

    public function testExecuteWhenCookieAlreadyMissing(): void
    {
        $this->storage->delete('access_token');

        $this->service->execute();

        $this->assertNull($this->storage->get('access_token'));
    }
}
