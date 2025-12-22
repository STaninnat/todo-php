<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Middlewares;

use App\Api\Middlewares\CorsMiddleware;
use App\Api\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class CorsMiddlewareUnitTest
 *
 * Unit tests for {@see CorsMiddleware}. verifies that the correct Cross-Origin
 * Resource Sharing (CORS) headers are applied to responses.
 *
 * Test scenarios include:
 * - Verifies allow-origin logic for whitelisted domains
 * - Verifies blocking of disallowed origins
 * - Verifies wildcard/default behavior
 *
 * @package Tests\Unit\Api\Middlewares
 */
class CorsMiddlewareUnitTest extends TestCase
{
    /**
     * Sets up the test environment.
     *
     * Loads the header mock helper to override the native header() function
     * and clears any previously captured headers.
     *
     * @return void
     */
    protected function setUp(): void
    {
        require_once __DIR__ . '/header_mock.php';
        x_cleanup_headers();
    }

    /**
     * Tears down the test environment.
     *
     * Clears any captured headers to ensure a clean state for the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        x_cleanup_headers();
    }

    /**
     * Tests that the middleware correctly adds CORS headers for allowed origins.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     *
     * @return void
     */
    public function testInvokeAddsCorsHeadersForAllowedOrigin(): void
    {
        $allowedOrigins = ['https://example.com'];
        $middleware = new CorsMiddleware($allowedOrigins);

        $request = $this->createMock(Request::class);
        $request->method = 'GET';

        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';

        ($middleware)($request);

        $headers = xdebug_get_headers();

        $this->assertContains('Access-Control-Allow-Origin: https://example.com', $headers);
        $this->assertContains('Access-Control-Allow-Credentials: true', $headers);
    }

    /**
     * Tests that the middleware blocks requests from disallowed origins.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     *
     * @return void
     */
    public function testInvokeBlocksDisallowedOrigin(): void
    {
        $allowedOrigins = ['https://trusted.com'];
        $middleware = new CorsMiddleware($allowedOrigins);

        $request = $this->createMock(Request::class);
        $request->method = 'GET';

        $_SERVER['HTTP_ORIGIN'] = 'https://malicious.com';

        ($middleware)($request);

        $headers = xdebug_get_headers();
        $this->assertContains('Access-Control-Allow-Origin: null', $headers);
    }

    /**
     * Tests that the middleware allows all origins when no origins are defined.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     *
     * @return void
     */
    public function testInvokeAllowsAllWhenNoOriginsDefined(): void
    {
        $middleware = new CorsMiddleware([]); // Empty allowed list

        $request = $this->createMock(Request::class);
        $request->method = 'GET';

        $_SERVER['HTTP_ORIGIN'] = 'https://anywhere.com';

        ($middleware)($request);

        $headers = xdebug_get_headers();
        $this->assertContains('Access-Control-Allow-Origin: https://anywhere.com', $headers);
    }
}
