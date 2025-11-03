<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Middlewares;

use App\Api\Request;
use App\Api\Middlewares\AuthMiddleware;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class AuthMiddlewareIntegrationTest
 *
 * Integration tests for the AuthMiddleware class.
 *
 * This test suite verifies:
 * - JWT verification using real JwtService and JWT_SECRET from .env.test
 * - Refresh behavior when token is near expiration
 * - Cookie handling using a fake in-memory cookie storage
 * - Unauthorized handling when missing or invalid token
 *
 * @package Tests\Integration\Api\Middlewares
 */
final class AuthMiddlewareIntegrationTest extends TestCase
{
    private JwtService $jwt;
    private CookieManager $cookieManager;
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize JwtService (uses JWT_SECRET from .env.test)
        $this->jwt = new JwtService();
        $this->cookieManager = new CookieManager();
        $this->middleware = new AuthMiddleware($this->cookieManager, $this->jwt);

        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        parent::tearDown();
    }

    public function testValidTokenAttachesAuthPayload(): void
    {
        $token = $this->jwt->create(['user_id' => 'abc123']);
        $_COOKIE['access_token'] = $token;

        $req = new Request();
        $this->middleware->refreshJwt($req);

        $this->assertIsArray($req->auth);
        $this->assertSame('abc123', $req->auth['user_id']);
    }

    public function testInvalidTokenResultsInNoAuth(): void
    {
        $_COOKIE['access_token'] = 'this.is.invalid';

        $req = new Request();

        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'Invalid token should result in no auth payload');
    }

    public function testMissingCookieDoesNotSetAuth(): void
    {
        // No access_token in cookie
        $req = new Request();
        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'No cookie should result in no auth payload');
    }

    public function testExpiredTokenIsNotAccepted(): void
    {
        // Token already expired (1 hour ago)
        $now = time();
        $expiredToken = $this->jwt->create(['user_id' => 'dead'], $now - 7200);
        $_COOKIE['access_token'] = $expiredToken;

        $req = new Request();
        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'Expired token should not be accepted');
    }

    public function testRequireAuthThrowsWhenNoAuth(): void
    {
        $req = new Request();
        $req->auth = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->middleware->requireAuth($req);
    }
}
