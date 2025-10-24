<?php

declare(strict_types=1);

namespace Tests\Unit\Api\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Router;
use App\Api\Request;
use App\Utils\JsonResponder;
use TypeError;

/**
 * Class RouterTypeErrTest
 *
 * Unit tests for validating strict type enforcement in the Router class.
 *
 * Each test ensures that invalid argument types passed to Router methods
 * (constructor, register, addMiddleware, dispatch) trigger PHP TypeError exceptions.
 * Also verifies that valid types do not throw.
 *
 * @package Tests\Unit\Api\TypeError
 * @covers \App\Api\Router
 */
class RouterTypeErrTest extends TestCase
{
    /**
     * Test that constructor throws TypeError when responderClass is not a string.
     *
     * @return void
     */
    public function testConstructorThrowsWhenResponderClassIsNotString(): void
    {
        $this->expectException(TypeError::class);

        // Invalid type: responderClass expects string but receives array
        new Router(responderClass: ['not-a-string']);
    }

    /**
     * Test that register() throws TypeError when HTTP method is not a string.
     *
     * @return void
     */
    public function testRegisterThrowsWhenMethodIsNotString(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        // Invalid type: method must be string
        $router->register(method: ['GET'], path: '/test', handler: fn() => null);
    }

    /**
     * Test that register() throws TypeError when path is not a string.
     *
     * @return void
     */
    public function testRegisterThrowsWhenPathIsNotString(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        // Invalid type: path must be string
        $router->register(method: 'GET', path: ['invalid'], handler: fn() => null);
    }

    /**
     * Test that register() throws TypeError when handler is not callable.
     *
     * @return void
     */
    public function testRegisterThrowsWhenHandlerIsNotCallable(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        // Invalid type: handler expects callable, got string
        $router->register('GET', '/x', 'not-callable');
    }

    /**
     * Test that register() throws TypeError when middlewares is not an array.
     *
     * @return void
     */
    public function testRegisterThrowsWhenMiddlewaresIsNotArray(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        // Invalid type: middlewares expects array
        $router->register('GET', '/x', fn() => null, middlewares: 'not-array');
    }

    /**
     * Test that addMiddleware() throws TypeError when middleware is not callable.
     *
     * @return void
     */
    public function testAddMiddlewareThrowsWhenMiddlewareIsNotCallable(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        // Invalid type: expects callable
        $router->addMiddleware('not-callable');
    }

    /**
     * Test that dispatch() throws TypeError when request is not a Request object.
     *
     * @return void
     */
    public function testDispatchThrowsWhenRequestIsNotRequestObject(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        /** @phpstan-ignore-next-line to simulate invalid type */
        $router->dispatch(request: 'invalid');
    }

    /**
     * Test that dispatch() throws TypeError when forTest argument is not boolean.
     *
     * @return void
     */
    public function testDispatchThrowsWhenForTestIsNotBool(): void
    {
        $router = new Router();
        $this->expectException(TypeError::class);

        /** @phpstan-ignore-next-line to simulate invalid type */
        $router->dispatch(null, forTest: 'not-bool');
    }

    /**
     * Test that constructing Router with a valid responder class string does not throw.
     *
     * @return void
     */
    public function testConstructWithValidStringResponderClassDoesNotThrow(): void
    {
        $router = new Router(JsonResponder::class);

        // Valid input: should instantiate normally
        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * Test that register() works without throwing when all argument types are valid.
     *
     * @return void
     */
    public function testRegisterWithValidTypesDoesNotThrow(): void
    {
        $router = new Router();
        $router->register('GET', '/users', fn() => ['ok' => true]);

        // Expect no exception; assertion confirms test passed
        $this->assertTrue(true);
    }

    /**
     * Test that addMiddleware() accepts a valid callable without throwing.
     *
     * @return void
     */
    public function testAddMiddlewareWithValidCallableDoesNotThrow(): void
    {
        $router = new Router();

        // Pass a valid callable (closure)
        $router->addMiddleware(fn(Request $r) => null);

        $this->assertTrue(true);
    }

    /**
     * Test that dispatch() runs correctly with valid arguments.
     *
     * Ensures Router executes and returns an array result when called with
     * a valid Request and `forTest: true` flag.
     *
     * @return void
     */
    public function testDispatchWithValidArgumentsDoesNotThrow(): void
    {
        $router = new Router();

        // Register a valid route handler
        $router->register('GET', '/ok', fn() => ['success' => true]);

        // Create a matching Request
        $req = new Request(method: 'GET', path: '/ok');

        // Execute dispatch in test mode
        $result = $router->dispatch($req, forTest: true);

        // Expect array result (response payload)
        $this->assertIsArray($result);
    }
}
