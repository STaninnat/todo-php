<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Middlewares;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Api\Middlewares\AuthMiddleware;
use App\Api\Request;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\Api\Exceptions\UnauthorizedException;

/**
 * Class AuthMiddlewareTest
 *
 * Unit tests for the AuthMiddleware class.
 *
 * This test suite verifies:
 * - refreshJwt() behavior with valid, invalid, and refresh-needed tokens
 * - Properly attaching decoded JWT payloads to the Request
 * - Refreshing tokens and saving them in cookies when required
 * - requireAuth() enforcing authentication and throwing RuntimeException if unauthorized
 *
 * Uses CookieManager and JwtService mocks to avoid real token or cookie handling.
 *
 * @package Tests\Unit\Api\Middlewares
 */
class AuthMiddlewareUnitTest extends TestCase
{
    /** @var CookieManager&\PHPUnit\Framework\MockObject\MockObject */
    private $cookieManager;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject */
    private $jwt;

    private AuthMiddleware $middleware;
    private Request $request;
    private int $now;

    /**
     * Setup common test dependencies:
     * 
     * - Mock CookieManager and JwtService
     * - Create AuthMiddleware with mocks
     * - Initialize Request instance
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->jwt = $this->createMock(JwtService::class);
        $this->middleware = new AuthMiddleware($this->cookieManager, $this->jwt);
        $this->request = new Request();
        $this->now = time();
    }

    /**
     * Test: refreshJwt() behavior for various token scenarios.
     *
     * @param string                    $token                      Token string returned by CookieManager
     * @param ?array<string,mixed>      $verifyResult               Decoded payload returned by JwtService::verify()
     * @param bool                      $shouldRefresh              Whether JwtService::shouldRefresh() returns true
     * @param ?string                   $refreshResult              New token returned by JwtService::refresh()
     * @param mixed                     $expectAuthSet              Expected value of $request->auth after refreshJwt()
     * @param bool                      $expectSetAccessTokenCalled Whether CookieManager::setAccessToken() should be called
     *
     * @return void
     */
    #[DataProvider('refreshJwtProvider')]
    public function testRefreshJwt(
        string $token,
        ?array $verifyResult,
        bool $shouldRefresh,
        ?string $refreshResult,
        $expectAuthSet,
        bool $expectSetAccessTokenCalled
    ): void {
        // JwtService::verify must always be called with the provided token
        $this->jwt->expects($this->once())
            ->method('verify')
            ->with($token)
            ->willReturn($verifyResult);

        // JwtService::shouldRefresh should be called only if verify succeeded
        $this->jwt->expects($verifyResult ? $this->once() : $this->never())
            ->method('shouldRefresh')
            ->with($verifyResult)
            ->willReturn($shouldRefresh);

        // If refresh is needed, return the new token
        if ($refreshResult !== null) {
            $this->jwt->method('refresh')->willReturn($refreshResult);
        }

        // CookieManager::setAccessToken should only be called if refresh occurs
        if ($expectSetAccessTokenCalled) {
            $this->cookieManager->expects($this->once())
                ->method('setAccessToken')
                ->with($refreshResult, $this->greaterThanOrEqual($this->now));
        } else {
            $this->cookieManager->expects($this->never())->method('setAccessToken');
        }

        // Mock the cookie to return the given token
        $this->cookieManager->method('getAccessToken')->willReturn($token);

        // Act: run refreshJwt
        $this->middleware->refreshJwt($this->request);

        // Assert: $request->auth must match expected payload
        $this->assertSame($expectAuthSet, $this->request->auth);
    }

    /**
     * Data provider for testRefreshJwt().
     *
     * Supplies different token/payload scenarios:
     * - valid token, no refresh needed
     * - valid token, refresh required
     * - invalid token
     * - empty payload
     * - payload missing user_id
     * - payload with future iat
     *
     * @return array<string, array{0: string, 1: ?array<string,int>, 2: bool, 3: ?string, 4: mixed, 5: bool}>
     */
    public static function refreshJwtProvider(): array
    {
        $now = time();
        return [
            'valid token, no refresh' => [
                'valid_token',
                ['user_id' => 1, 'iat' => $now],
                false,
                null,
                ['user_id' => 1, 'iat' => $now],
                false,
            ],
            'valid token, needs refresh' => [
                'valid_token',
                ['user_id' => 2, 'iat' => $now - 3500],
                true,
                'new_token',
                ['user_id' => 2, 'iat' => $now - 3500],
                true,
            ],
            'invalid token' => [
                'invalid_token',
                null,
                false,
                null,
                null,
                false,
            ],
            'empty payload' => [
                'empty_payload',
                [],
                false,
                null,
                null,
                false,
            ],
            'payload missing user_id' => [
                'no_user_id',
                ['iat' => $now - 1000],
                false,
                null,
                ['iat' => $now - 1000],
                false,
            ],
            'payload with future iat' => [
                'future_iat',
                ['user_id' => 3, 'iat' => $now + 3600],
                false,
                null,
                ['user_id' => 3, 'iat' => $now + 3600],
                false,
            ],
        ];
    }

    /**
     * Test: requireAuth() should throw if no authentication payload exists.
     *
     * @return void
     */
    public function testRequireAuthThrowsWithoutAuth(): void
    {
        $this->request->auth = null;

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized. You must be logged in.');

        $this->middleware->requireAuth($this->request);
    }

    /**
     * Test: requireAuth() should pass when auth payload exists.
     *
     * @return void
     */
    public function testRequireAuthPassesWithAuth(): void
    {
        $payload = ['user_id' => 1];
        $this->request->auth = $payload;

        $this->middleware->requireAuth($this->request);

        $this->assertSame($payload, $this->request->auth);
    }
}
