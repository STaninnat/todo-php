<?php
require_once __DIR__ . '/Request.php';

class Router
{
    private array $routes = []; // [METHOD][PATH] => ['handler' => callable, 'middlewares' => []]
    private array $globalMiddlewares = [];

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
    public function dispatch(): void
    {
        $request = new Request();
        $method = $request->method;
        $path = rtrim($request->path, '/');
        if ($path === '') $path = '/';

        try {
            if (!isset($this->routes[$method][$path])) {
                throw new InvalidArgumentException("Route not found: $method $path");
            }

            $route = $this->routes[$method][$path];

            // Run global middlewares
            foreach ($this->globalMiddlewares as $mw) {
                $mw($request);
            }

            // Run route-specific middlewares
            foreach ($route['middlewares'] as $mw) {
                $mw($request);
            }

            // Call handler
            $handler = $route['handler'];
            $reflection = new ReflectionFunction($handler);
            if ($reflection->getNumberOfParameters() > 0) {
                $handler($request);
            } else {
                $handler();
            }
        } catch (Exception $e) {
            // You can add HTTP status code here if needed
            jsonResponse(false, 'error', $e->getMessage());
        }
    }
}
