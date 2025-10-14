<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Router;
use App\Api\Request;
use App\Utils\JsonResponder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class RouterTest
 *
 * Unit tests for the Router class.
 *
 * Covers various routing behaviors including:
 * - Dispatching handlers with/without Request parameter
 * - Middleware execution order (global and route-specific)
 * - Exception handling and response normalization
 * - Handling of routes returning different data types
 *
 * @package Tests\Unit\Api
 */
class RouterTest extends TestCase
{
    /**
     * Test that a registered handler executes correctly
     * when it does not require a Request parameter.
     *
     * Ensures Router correctly matches path and method.
     *
     * @return void
     */
    public function testDispatchHandlerWithoutRequest(): void
    {
        $router = new Router();
        // Register a GET route returning a simple success response
        $router->register('GET', '/hello', fn() => JsonResponder::success('hi'));

        $req = new Request(method: 'GET', path: '/hello');
        $response = $router->dispatch($req, true);

        // Validate success structure returned by JsonResponder
        $this->assertEquals([
            'success' => true,
            'type' => 'success',
            'message' => 'hi'
        ], $response);
    }

    /**
     * Test that handler receives the Request instance
     * and can access its properties.
     *
     * @return void
     */
    public function testDispatchHandlerWithRequest(): void
    {
        $router = new Router();
        // Register a POST route expecting a Request argument
        $router->register('POST', '/echo', fn(Request $r) => JsonResponder::success('method=' . $r->method));

        $req = new Request(method: 'POST', path: '/echo');
        $response = $router->dispatch($req, true);

        // Assert that handler correctly accessed $r->method
        $this->assertEquals('method=POST', $response['message']);
    }

    /**
     * Test that handler returning a raw array
     * is passed through as-is by Router::dispatch().
     *
     * @return void
     */
    public function testHandlerReturnsArray(): void
    {
        $router = new Router();
        $router->register('GET', '/array', fn() => ['foo' => 'bar']);

        $req = new Request(method: 'GET', path: '/array');
        $response = $router->dispatch($req, true);

        // Assert raw array response is preserved
        $this->assertEquals(['foo' => 'bar'], $response);
    }

    /**
     * Test that global middleware executes before route-specific middleware,
     * and that both are called in correct order.
     *
     * @return void
     */
    public function testGlobalAndRouteMiddlewares(): void
    {
        $router = new Router();
        $called = [];

        // Add global middleware
        $router->addMiddleware(function ($req) use (&$called) {
            $called[] = 'global';
        });

        // Register route with its own middleware
        $router->register('GET', '/test', fn() => JsonResponder::success('ok'), [
            function ($req) use (&$called) {
                $called[] = 'route';
            }
        ]);

        $req = new Request(method: 'GET', path: '/test');
        $router->dispatch($req, true);

        // Assert order of middleware execution
        $this->assertEquals(['global', 'route'], $called);
    }

    /**
     * Test that dispatch returns a failure response
     * when route is not found.
     *
     * @return void
     */
    public function testRouteNotFound(): void
    {
        $router = new Router();
        $req = new Request(method: 'GET', path: '/not-found');

        $response = $router->dispatch($req, true);

        // Expect a standardized "Route not found" message
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Route not found', $response['message']);
    }

    /**
     * Test that a handler throwing RuntimeException
     * returns an appropriate error response.
     *
     * @return void
     */
    public function testHandlerThrowsRuntimeException(): void
    {
        $router = new Router();
        // Register a route that triggers a runtime error
        $router->register('GET', '/boom', fn() => throw new RuntimeException('fail'));

        $req = new Request(method: 'GET', path: '/boom');
        $response = $router->dispatch($req, true);

        // Router should catch and normalize the exception
        $this->assertFalse($response['success']);
        $this->assertEquals('fail', $response['message']);
    }

    /**
     * Test that generic Exception is caught and converted
     * into a generic "Internal Server Error" response.
     *
     * @return void
     */
    public function testHandlerThrowsGenericException(): void
    {
        $router = new Router();
        // Register route that throws a base Exception
        $router->register('GET', '/oops', fn() => throw new \Exception('unexpected'));

        $req = new Request(method: 'GET', path: '/oops');
        $response = $router->dispatch($req, true);

        // Ensure internal errors are handled gracefully
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Internal Server Error', $response['message']);
    }
}
