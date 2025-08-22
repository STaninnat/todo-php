<?php

/**
 * Simple Router class for handling HTTP requests and actions.
 */
class Router
{
    // Stores all registered routes in the format [METHOD][ACTION] => handler
    private array $routes = [];

    // Stores middleware functions to be executed before route handlers
    private array $middlewares = [];

    /**
     * Register a route with a specific HTTP method and action.
     *
     * @param string   $method  HTTP method (GET, POST, etc.)
     * @param string   $action  Action name to match
     * @param callable $handler Function to execute when route is matched
     */
    public function register(string $method, string $action, callable $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$action] = $handler;
    }

    /**
     * Add a middleware function to be executed before route handlers.
     *
     * @param callable $middleware Middleware function accepting ($method, $action)
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Dispatch the request by executing the matched route handler.
     * Executes all middlewares before calling the handler.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];   // Current HTTP method
        $action = $_GET['action'] ?? $_POST['action'] ?? null;  // Determine action from GET or POST

        try {
            // Check if the route exists for the given method and action
            if (!isset($this->routes[$method][$action])) {
                throw new InvalidArgumentException("Unknown action: $action for method: $method");
            }

            // Execute all registered middlewares
            foreach ($this->middlewares as $middleware) {
                $middleware($method, $action);
            }

            // Call the registered route handler
            call_user_func($this->routes[$method][$action]);
        } catch (Exception $e) {
            // Handle exceptions with JSON error response
            jsonResponse(false, 'error', $e->getMessage());
        }
    }
}
