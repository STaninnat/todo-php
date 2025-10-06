<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Router;
use App\Api\Request;
use App\Utils\JsonResponder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouterTest extends TestCase
{
    public function testDispatchHandlerWithoutRequest()
    {
        $router = new Router();
        $router->register('GET', '/hello', fn() => JsonResponder::success('hi'));

        $req = new Request(method: 'GET', path: '/hello');
        $response = $router->dispatch($req, true);

        $this->assertEquals([
            'success' => true,
            'type' => 'success',
            'message' => 'hi'
        ], $response);
    }

    public function testDispatchHandlerWithRequest()
    {
        $router = new Router();
        $router->register('POST', '/echo', fn(Request $r) => JsonResponder::success('method=' . $r->method));

        $req = new Request(method: 'POST', path: '/echo');
        $response = $router->dispatch($req, true);

        $this->assertEquals('method=POST', $response['message']);
    }

    public function testHandlerReturnsArray()
    {
        $router = new Router();
        $router->register('GET', '/array', fn() => ['foo' => 'bar']);

        $req = new Request(method: 'GET', path: '/array');
        $response = $router->dispatch($req, true);

        $this->assertEquals(['foo' => 'bar'], $response);
    }

    public function testGlobalAndRouteMiddlewares()
    {
        $router = new Router();
        $called = [];

        $router->addMiddleware(function ($req) use (&$called) {
            $called[] = 'global';
        });

        $router->register('GET', '/test', fn() => JsonResponder::success('ok'), [
            function ($req) use (&$called) {
                $called[] = 'route';
            }
        ]);

        $req = new Request(method: 'GET', path: '/test');
        $router->dispatch($req, true);

        $this->assertEquals(['global', 'route'], $called);
    }

    public function testRouteNotFound()
    {
        $router = new Router();
        $req = new Request(method: 'GET', path: '/not-found');

        $response = $router->dispatch($req, true);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Route not found', $response['message']);
    }

    public function testHandlerThrowsRuntimeException()
    {
        $router = new Router();
        $router->register('GET', '/boom', fn() => throw new RuntimeException('fail'));

        $req = new Request(method: 'GET', path: '/boom');
        $response = $router->dispatch($req, true);

        $this->assertFalse($response['success']);
        $this->assertEquals('fail', $response['message']);
    }

    public function testHandlerThrowsGenericException()
    {
        $router = new Router();
        $router->register('GET', '/oops', fn() => throw new \Exception('unexpected'));

        $req = new Request(method: 'GET', path: '/oops');
        $response = $router->dispatch($req, true);

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Internal Server Error', $response['message']);
    }
}
