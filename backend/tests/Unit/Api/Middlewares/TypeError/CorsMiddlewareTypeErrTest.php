<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Middlewares\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Middlewares\CorsMiddleware;
use TypeError;
use stdClass;

/**
 * Class CorsMiddlewareTypeErrTest
 *
 * Unit tests for verifying TypeError enforcement in CorsMiddleware
 * when strict types are enabled via declare(strict_types=1).
 *
 * This test suite ensures:
 * - Constructor throws TypeError if allowedOrigins is not an array
 * - __invoke throws TypeError if argument is not a Request instance
 *
 * @package Tests\Unit\Api\Middlewares
 */
class CorsMiddlewareTypeErrTest extends TestCase
{
    /**
     * Test: Constructor throws TypeError if allowedOrigins is not an array.
     *
     * @return void
     */
    public function testConstructorWithInvalidAllowedOriginsType(): void
    {
        $this->expectException(TypeError::class);

        // Sending string instead of array should cause TypeError
        new CorsMiddleware("not_an_array");
    }

    /**
     * Test: __invoke() throws TypeError if argument is not a Request instance.
     *
     * @return void
     */
    public function testInvokeWithInvalidRequestType(): void
    {
        $this->expectException(TypeError::class);

        $middleware = new CorsMiddleware([]);

        // Sending stdClass instead of Request should cause TypeError
        $middleware(new stdClass());
    }
}
