<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Middlewares;

use PHPUnit\Framework\TestCase;
use App\Api\Middlewares\AuthMiddleware;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use App\Api\Request;
use TypeError;

/**
 * Class AuthMiddlewareTypeErrTest
 *
 * Unit tests for verifying TypeError enforcement in AuthMiddleware
 * when strict types are enabled via declare(strict_types=1).
 *
 * This test suite ensures:
 * - Constructor throws TypeError if passed invalid types
 * - Methods throw TypeError if arguments are not of expected type
 *
 * @package Tests\Unit\Api\Middlewares
 */
class AuthMiddlewareTypeErrTest extends TestCase
{
    /**
     * Test: Constructor throws TypeError if cookieManager is not a CookieManager instance.
     *
     * @return void
     */
    public function testConstructorWithInvalidCookieManagerType(): void
    {
        $this->expectException(TypeError::class);

        // Sending the cookieManager as a string instead of a CookieManager will cause a TypeError.
        new AuthMiddleware("not_a_cookie_manager", $this->createMock(JwtService::class));
    }

    /**
     * Test: Constructor throws TypeError if jwt is not a JwtService instance.
     *
     * @return void
     */
    public function testConstructorWithInvalidJwtServiceType(): void
    {
        $this->expectException(TypeError::class);

        // Sending the jwt as an int instead of an JwtService will cause a TypeError.
        new AuthMiddleware($this->createMock(CookieManager::class), 123);
    }

    /**
     * Test: refreshJwt() throws TypeError if argument is not a Request instance.
     *
     * @return void
     */
    public function testRefreshJwtWithInvalidRequestType(): void
    {
        $this->expectException(TypeError::class);
        $middleware = new AuthMiddleware(
            $this->createMock(CookieManager::class),
            $this->createMock(JwtService::class)
        );

        // Sending the refreshJwt as an array instead of a Request will cause a TypeError.
        $middleware->refreshJwt([]);
    }

    /**
     * Test: requireAuth() throws TypeError if argument is not a Request instance.
     *
     * @return void
     */
    public function testRequireAuthWithInvalidRequestType(): void
    {
        $this->expectException(TypeError::class);
        $middleware = new AuthMiddleware(
            $this->createMock(CookieManager::class),
            $this->createMock(JwtService::class)
        );

        // Sending the requireAuth as a stdClass instead of a Request will cause a TypeError.
        $middleware->requireAuth(new \stdClass());
    }
}
