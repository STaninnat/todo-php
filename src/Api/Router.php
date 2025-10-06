<?php

namespace App\Api;

use App\Utils\JsonResponder;
use InvalidArgumentException;
use Exception;
use RuntimeException;

class Router
{
    /** @var array<string, array<string, array{handler: callable, middlewares: callable[]}> */
    private array $routes = [];

    /** @var callable[] */
    private array $globalMiddlewares = [];

    private string $responderClass;

    public function __construct(string $responderClass = JsonResponder::class)
    {
        $this->responderClass = $responderClass;
    }

    /**
     * Register a route with HTTP method and path
     */
    public function register(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/'); // normalize path
        if ($path === '') $path = '/';

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Add global middleware
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    /**
     * Dispatch the incoming request
     */
    public function dispatch(?Request $request = null, bool $forTest = false): ?array
    {
        $request ??= new Request();
        $method = $request->method;
        $path = rtrim($request->path, '/');
        if ($path === '') $path = '/';

        $Responder = $this->responderClass;

        try {
            if (!isset($this->routes[$method][$path])) {
                throw new InvalidArgumentException("Route not found: $method $path");
            }

            $route = $this->routes[$method][$path];

            // Run middlewares
            $this->runMiddlewares($this->globalMiddlewares, $request);
            $this->runMiddlewares($route['middlewares'], $request);

            // Call handler
            $handler = $route['handler'];
            $result = $this->callHandler($handler, $request);

            // If the handler returns a JsonResponder, send the response.
            if (is_object($result) && $result instanceof $Responder) {
                $response = $result->send(false, $forTest);
                return $forTest ? $response : null;
            }

            // For handlers that return arrays or other values
            return $forTest ? $result : null;
        } catch (InvalidArgumentException $e) {
            $response = $Responder::error($e->getMessage())->send(false, $forTest);
            return $forTest ? $response : null;
        } catch (RuntimeException $e) {
            $response = $Responder::error($e->getMessage())->send(false, $forTest);
            return $forTest ? $response : null;
        } catch (Exception $e) {
            $response = $Responder::error('Internal Server Error: ' . $e->getMessage())->send(false, $forTest);
            return $forTest ? $response : null;
        }
    }

    private function runMiddlewares(array $middlewares, Request $request): void
    {
        foreach ($middlewares as $mw) {
            $mw($request);
        }
    }

    private function callHandler(callable $handler, Request $request)
    {
        $ref = is_array($handler)
            ? new \ReflectionMethod($handler[0], $handler[1])
            : new \ReflectionFunction($handler);

        return $ref->getNumberOfParameters() > 0
            ? call_user_func($handler, $request)
            : call_user_func($handler);
    }
}
