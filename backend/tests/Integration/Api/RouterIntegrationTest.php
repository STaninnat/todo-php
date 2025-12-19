<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use App\Api\Request;
use App\Api\Router;
use App\Api\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class RouterIntegrationTest
 *
 * Integration tests for the {@see Router} component. Ensures that
 * route registration, middleware execution, request dispatching,
 * error handling, and request injection behave as expected in
 * real usage scenarios.
 *
 * - Validates successful route dispatching
 * - Confirms correct fallback behavior for missing routes
 * - Ensures global and per-route middleware execution order
 * - Verifies handler exception handling
 * - Tests type and method validation under integrated flow
 *
 * @package Tests\Integration\Api
 */
class RouterIntegrationTest extends TestCase
{
    /** @var Router Router instance used for testing */
    private Router $router;

    /**
     * Set up a fresh router instance for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    /**
     * Ensure a registered route is properly dispatched
     * and returns its handler output.
     *
     * @return void
     */
    public function testRegisterAndDispatch(): void
    {
        $this->router->register('GET', '/test', function () {
            return ['message' => 'success'];
        });

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertSame('success', $response['message']);
    }

    /**
     * Ensure unknown routes produce an expected error response.
     *
     * @return void
     */
    public function testRouteNotFound(): void
    {
        $request = new Request('GET', '/not-found');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertIsString($response['message']);
        $this->assertStringContainsString('Route not found', $response['message']);
    }

    /**
     * Validate that global middleware is executed before route handlers
     * and can modify the request object.
     *
     * @return void
     */
    public function testGlobalMiddleware(): void
    {
        $this->router->addMiddleware(function (Request $request) {
            $request->params['global'] = true;
        });

        $this->router->register('GET', '/middleware', function (Request $request) {
            return ['global' => $request->params['global'] ?? false];
        });

        $request = new Request('GET', '/middleware');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['global']);
    }

    /**
     * Validate route-specific middleware execution.
     *
     * @return void
     */
    public function testRouteMiddleware(): void
    {
        $middleware = function (Request $request) {
            $request->params['route'] = true;
        };

        $this->router->register('GET', '/route-middleware', function (Request $request) {
            return ['route' => $request->params['route'] ?? false];
        }, [$middleware]);

        $request = new Request('GET', '/route-middleware');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['route']);
    }

    /**
     * Ensure middleware execution order:
     * global → route-specific → handler.
     *
     * @return void
     */
    public function testMiddlewareOrder(): void
    {
        $this->router->addMiddleware(function (Request $request) {
            $request->params['order'] = 'global';
        });

        $middleware = function (Request $request) {
            /** @var string $order */
            $order = $request->params['order'] ?? '';
            $request->params['order'] = $order . '-route';
        };

        $this->router->register('GET', '/order', function (Request $request) {
            return ['order' => $request->params['order']];
        }, [$middleware]);

        $request = new Request('GET', '/order');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertSame('global-route', $response['order']);
    }

    /**
     * Ensure thrown exceptions inside handlers are converted into
     * proper error responses.
     *
     * @return void
     */
    public function testHandlerException(): void
    {
        $this->router->register('GET', '/error', function () {
            throw new RuntimeException('Something went wrong');
        });

        $request = new Request('GET', '/error');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertIsString($response['message']);
        $this->assertStringContainsString('Something went wrong', $response['message']);
    }

    /**
     * Ensure Request injection works and request body values
     * are available within the handler.
     *
     * @return void
     */
    public function testRequestInjection(): void
    {
        $this->router->register('POST', '/data', function (Request $request) {
            return ['data' => $request->body['value']];
        });

        $request = new Request('POST', '/data', null, null, ['value' => 123]);
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertSame(123, $response['data']);
    }

    /**
     * Ensure incorrect HTTP methods produce a "route not found" response.
     *
     * @return void
     */
    public function testMethodMismatch(): void
    {
        $this->router->register('POST', '/only-post', function () {
            return ['status' => 'ok'];
        });

        $request = new Request('GET', '/only-post');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertIsString($response['message']);
        $this->assertStringContainsString('Route not found', $response['message']);
    }

    /**
     * Ensure UnauthorizedException produces a 401 error response.
     *
     * @return void
     */
    public function testHandlerThrowsUnauthorizedException(): void
    {
        $this->router->register('GET', '/protected', function () {
            throw new UnauthorizedException('Access Denied');
        });

        $request = new Request('GET', '/protected');
        $response = $this->router->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertSame('Access Denied', $response['message']);
    }
}
