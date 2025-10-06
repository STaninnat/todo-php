<?php

declare(strict_types=1);

namespace Tests\Unit\Api\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Router;
use App\Api\Request;
use App\Utils\JsonResponder;
use TypeError;

class RouterTypeErrTest extends TestCase
{
    public function testConstructorThrowsWhenResponderClassIsNotString(): void
    {
        $this->expectException(TypeError::class);
        new Router(responderClass: ['not-a-string']);
    }

    public function testRegisterThrowsWhenMethodIsNotString(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        $router->register(method: ['GET'], path: '/test', handler: fn() => null);
    }

    public function testRegisterThrowsWhenPathIsNotString(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        $router->register(method: 'GET', path: ['invalid'], handler: fn() => null);
    }

    public function testRegisterThrowsWhenHandlerIsNotCallable(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        $router->register('GET', '/x', 'not-callable');
    }

    public function testRegisterThrowsWhenMiddlewaresIsNotArray(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        $router->register('GET', '/x', fn() => null, middlewares: 'not-array');
    }

    public function testAddMiddlewareThrowsWhenMiddlewareIsNotCallable(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        $router->addMiddleware('not-callable');
    }

    public function testDispatchThrowsWhenRequestIsNotRequestObject(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        /** @phpstan-ignore-next-line */
        $router->dispatch(request: 'invalid');
    }

    public function testDispatchThrowsWhenForTestIsNotBool(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);
        /** @phpstan-ignore-next-line */
        $router->dispatch(null, forTest: 'not-bool');
    }

    public function testConstructWithValidStringResponderClassDoesNotThrow(): void
    {
        $router = new Router(JsonResponder::class);
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testRegisterWithValidTypesDoesNotThrow(): void
    {
        $router = new Router();
        $router->register('GET', '/users', fn() => ['ok' => true]);
        $this->assertTrue(true); // no exception
    }

    public function testAddMiddlewareWithValidCallableDoesNotThrow(): void
    {
        $router = new Router();
        $router->addMiddleware(fn(Request $r) => null);
        $this->assertTrue(true);
    }

    public function testDispatchWithValidArgumentsDoesNotThrow(): void
    {
        $router = new Router();
        $router->register('GET', '/ok', fn() => ['success' => true]);
        $req = new Request(method: 'GET', path: '/ok');
        $result = $router->dispatch($req, forTest: true);
        $this->assertIsArray($result);
    }
}
