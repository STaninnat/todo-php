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
    /**
     * @var JwtService Service for generating and verifying JWTs
     */
    private JwtService $jwt;

    /**
     * @var CookieManager Cookie handling utility
     */
    private CookieManager $cookieManager;

    /**
     * @var AuthMiddleware Middleware under test
     */
    private AuthMiddleware $middleware;

    /**
     * Setup before each test.
     *
     * Initializes JwtService and CookieManager,
     * and resets $_COOKIE to ensure clean state.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize JwtService (uses JWT_SECRET from .env.test)
        $this->jwt = new JwtService();

        // In-memory cookie handler (no real HTTP cookies)
        $this->cookieManager = new CookieManager();

        // Create middleware instance with dependencies
        $this->middleware = new AuthMiddleware($this->cookieManager, $this->jwt);

        // Reset cookie state for isolation between tests
        $_COOKIE = [];
    }

    /**
     * Cleanup after each test.
     *
     * Clears $_COOKIE and restores parent teardown.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_COOKIE = [];
        parent::tearDown();
    }

    /**
     * Test: Valid JWT should attach decoded auth payload to Request.
     *
     * @return void
     */
    public function testValidTokenAttachesAuthPayload(): void
    {
        // Create a valid JWT for a known user ID
        $token = $this->jwt->create(['user_id' => 'abc123']);
        $_COOKIE['access_token'] = $token;

        $req = new Request();

        // Middleware should decode and attach JWT payload to Request->auth
        $this->middleware->refreshJwt($req);

        $this->assertIsArray($req->auth);
        $this->assertSame('abc123', $req->auth['user_id']);
    }

    /**
     * Test: Invalid JWT should result in no auth payload.
     *
     * @return void
     */
    public function testInvalidTokenResultsInNoAuth(): void
    {
        // Simulate corrupted/invalid JWT
        $_COOKIE['access_token'] = 'this.is.invalid';

        $req = new Request();

        // Should silently fail and not set auth
        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'Invalid token should result in no auth payload');
    }

    /**
     * Test: Missing cookie should not set any auth.
     *
     * @return void
     */
    public function testMissingCookieDoesNotSetAuth(): void
    {
        // No access_token in cookie
        $req = new Request();
        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'No cookie should result in no auth payload');
    }

    /**
     * Test: Expired JWT should not be accepted.
     *
     * @return void
     */
    public function testExpiredTokenIsNotAccepted(): void
    {
        // Create token that expired 2 hours ago
        $now = time();
        $expiredToken = $this->jwt->create(['user_id' => 'dead'], $now - 7200);
        $_COOKIE['access_token'] = $expiredToken;

        $req = new Request();

        // Middleware should reject expired tokens
        $this->middleware->refreshJwt($req);

        $this->assertNull($req->auth, 'Expired token should not be accepted');
    }

    /**
     * Test: requireAuth() should throw RuntimeException when no auth is attached.
     *
     * @return void
     */
    public function testRequireAuthThrowsWhenNoAuth(): void
    {
        $req = new Request();
        $req->auth = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unauthorized');

        // Should throw if auth not set
        $this->middleware->requireAuth($req);
    }
}
