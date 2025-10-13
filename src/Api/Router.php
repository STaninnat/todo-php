<?php

namespace App\Api;

use App\Utils\JsonResponder;
use InvalidArgumentException;
use Exception;
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
        if ($path === '') $path = '/';

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
     * @return array|null JSON response (when $forTest is true), otherwise null
     */
    public function dispatch(?Request $request = null, bool $forTest = false): ?array
    {
        // Create a new Request object if not provided
        $request ??= new Request();

        // Normalize HTTP method and path
        $method = $request->method;
        $path = rtrim($request->path, '/');
        if ($path === '') $path = '/';

        $Responder = $this->responderClass;

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
            $handler = $route['handler'];
            $result = $this->callHandler($handler, $request);

            // If handler returns a JsonResponder, send it
            if (is_object($result) && $result instanceof $Responder) {
                $response = $result->send(false, $forTest);
                return $forTest ? $response : null;
            }

            // For handlers returning arrays or primitives
            return $forTest ? $result : null;
        } catch (InvalidArgumentException $e) {
            // Handle invalid route or parameter errors
            $response = $Responder::error($e->getMessage())->send(false, $forTest);
            return $forTest ? $response : null;
        } catch (RuntimeException $e) {
            // Handle runtime-specific errors (e.g., DB or logic)
            $response = $Responder::error($e->getMessage())->send(false, $forTest);
            return $forTest ? $response : null;
        } catch (Exception $e) {
            // Catch-all for unhandled exceptions
            $response = $Responder::error('Internal Server Error: ' . $e->getMessage())->send(false, $forTest);
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
     *
     * @param callable $handler Route handler
     * @param Request  $request Request instance
     *
     * @return mixed Handler return value
     */
    private function callHandler(callable $handler, Request $request)
    {
        // Use reflection to inspect handler parameters
        $ref = is_array($handler)
            ? new \ReflectionMethod($handler[0], $handler[1])
            : new \ReflectionFunction($handler);

        // If handler expects parameters, inject Request
        return $ref->getNumberOfParameters() > 0
            ? call_user_func($handler, $request)
            : call_user_func($handler);
    }
}
