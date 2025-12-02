<?php

namespace App\Api;

use App\Utils\JsonResponder;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

/**
 * Class Router
 *
 * Lightweight HTTP router responsible for:
 * - Registering routes by HTTP method and path
 * - Managing global and per-route middlewares
 * - Dispatching requests and invoking handlers
 * - Handling exceptions and generating standardized JSON responses
 *
 * @package App\Api
 */
class Router
{
    /**
     * @var array<string, array<string, array{handler: callable, middlewares: callable[]}>>
     * Route definitions grouped by HTTP method and normalized path.
     */
    private array $routes = [];

    /**
     * @var callable[] List of global middlewares executed before all route handlers.
     */
    private array $globalMiddlewares = [];

    /**
     * @var string Fully qualified class name of the responder (e.g., JsonResponder).
     */
    private string $responderClass;

    /**
     * Constructor
     *
     * @param string $responderClass Class name of responder used for sending JSON responses
     */
    public function __construct(string $responderClass = JsonResponder::class)
    {
        $this->responderClass = $responderClass;
    }

    /**
     * Register a new route with its HTTP method, path, handler, and optional middlewares.
     *
     * - Normalizes path by trimming trailing slashes
     * - Stores callable handler and associated middleware list
     *
     * @param string        $method      HTTP method (e.g., GET, POST)
     * @param string        $path        Route path (e.g., /api/v1/users)
     * @param callable      $handler     Route handler function or method
     * @param callable[]    $middlewares Optional list of middlewares
     *
     * @return void
     */
    public function register(string $method, string $path, callable $handler, array $middlewares = []): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/'); // normalize path
        if ($path === '')
            $path = '/';

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Add a global middleware that runs before every request handler.
     *
     * @param callable $middleware Middleware function taking a {@see Request} as parameter
     *
     * @return void
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    /**
     * Dispatch the incoming request to the appropriate route handler.
     *
     * - Matches the route based on HTTP method and normalized path
     * - Executes global and route-specific middlewares
     * - Calls the assigned handler via reflection
     * - Handles exceptions and returns formatted JSON responses
     *
     * @param Request|null $request Optional request instance (auto-created if null)
     * @param bool         $forTest If true, returns the response array instead of sending it
     *
     * @return array<string, mixed>|null JSON response (when $forTest is true), otherwise null
     */
    public function dispatch(?Request $request = null, bool $forTest = false): ?array
    {
        // Create a new Request object if not provided
        $request ??= new Request();

        // Normalize HTTP method and path
        $method = $request->method;
        $path = rtrim($request->path, '/');
        if ($path === '')
            $path = '/';

        $responderClass = $this->responderClass;

        try {
            // Ensure route exists for this method/path
            if (!isset($this->routes[$method][$path])) {
                throw new InvalidArgumentException("Route not found: $method $path");
            }

            $route = $this->routes[$method][$path];

            // Run global middlewares first, then route-specific ones
            $this->runMiddlewares($this->globalMiddlewares, $request);
            $this->runMiddlewares($route['middlewares'], $request);

            // Execute route handler
            $result = $this->callHandler($route['handler'], $request, $forTest);

            // Handler returns JsonResponder
            if ($result instanceof JsonResponder) {
                /** @var array<string, mixed> $response */
                $response = (array) $result->send(false, $forTest);
                return $forTest ? $response : null;
            }

            // Handler returns array
            if ($forTest && is_array($result)) {
                /** @var array<string, mixed> $result */
                return $result;
            }

            // Otherwise return null
            return null;
        } catch (InvalidArgumentException $e) {
            // Handle invalid route or parameter errors
            /** @var JsonResponder $responder */
            $responder = $responderClass::error($e->getMessage());

            /** @var array<string, mixed> $response */
            $response = (array) $responder->send(false, $forTest);

            return $forTest ? $response : null;
        } catch (RuntimeException $e) {
            // Handle runtime-specific errors (e.g., DB or logic)
            /** @var JsonResponder $responder */
            $responder = $responderClass::error($e->getMessage());

            /** @var array<string, mixed> $response */
            $response = (array) $responder->send(false, $forTest);

            return $forTest ? $response : null;
        } catch (Exception $e) {
            // Catch-all for unhandled exceptions
            /** @var JsonResponder $responder */
            $responder = $responderClass::error('Internal Server Error: ' . $e->getMessage());

            /** @var array<string, mixed> $response */
            $response = (array) $responder->send(false, $forTest);

            return $forTest ? $response : null;
        }
    }

    /**
     * Execute an array of middlewares sequentially.
     *
     * @param callable[] $middlewares List of middleware functions
     * @param Request    $request     The request instance to pass to each middleware
     *
     * @return void
     */
    private function runMiddlewares(array $middlewares, Request $request): void
    {
        foreach ($middlewares as $mw) {
            $mw($request);
        }
    }

    /**
     * Invoke a route handler using reflection to determine if it accepts parameters.
     *
     * - Supports both function and [object, method] handler styles
     * - Calls the handler with {@see Request} if required
     * - Passes $forTest if the handler accepts a second argument
     *
     * @param callable $handler Route handler
     * @param Request  $request Request instance
     * @param bool     $forTest Whether we are in test mode
     *
     * @return mixed Handler return value (array, JsonResponder, or primitive)
     */
    private function callHandler(callable $handler, Request $request, bool $forTest = false)
    {
        // Use reflection to inspect handler parameters
        if ($handler instanceof Closure) {
            $ref = new ReflectionFunction($handler);
        } elseif (is_array($handler) && count($handler) === 2) {
            /** @var object|string $obj */
            $obj = $handler[0];
            /** @var string $method */
            $method = $handler[1];
            $ref = new ReflectionMethod($obj, $method);
        } else {
            throw new InvalidArgumentException('Handler must be Closure or [object, method]');
        }

        $params = $ref->getParameters();
        $args = [];

        if (count($params) > 0) {
            $args[] = $request;
        }
        if (count($params) > 1) {
            $args[] = $forTest;
        }

        return call_user_func_array($handler, $args);
    }
}
