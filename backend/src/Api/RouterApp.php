<?php

declare(strict_types=1);

namespace App\Api;

use App\Utils\Logger;
use App\Utils\JsonResponder;
use App\DB\Database;
use App\DB\UserQueries;
use App\DB\TaskQueries;
use App\Api\Auth\Controller\UserController;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Middlewares\AuthMiddleware;
use App\Utils\CookieManager;
use App\Utils\JwtService;
use Throwable;

/**
 * Class RouterApp
 *
 * Central application router responsible for:
 * - Registering routes and middlewares
 * - Instantiating controllers and dependencies
 * - Handling request dispatching with error logging
 *
 * @package App\Api
 */
class RouterApp
{
    /** @var Router Core router instance responsible for request routing and dispatching */
    private Router $router;

    /** @var Logger Application-wide logger for info, warning, and error messages */
    private Logger $logger;

    /** @var string Base prefix for all API routes (e.g., "/v1") */
    private string $apiPrefix;

    /** @var UserController Handles all user-related endpoints (signup, signin, update, delete, etc.) */
    private UserController $userController;

    /** @var TaskController Handles all task-related endpoints (add, update, delete, mark done, fetch) */
    private TaskController $taskController;

    /** @var AuthMiddleware Middleware that manages authentication checks and JWT refresh */
    private AuthMiddleware $authMiddleware;

    /**
     * Constructor
     *
     * Initializes the router, logger, and controllers with all required
     * dependencies (queries, services, middlewares, etc.), then registers
     * routes and global middlewares.
     *
     * @param Router          $router         Router instance for request handling
     * @param Logger          $logger         Application logger
     * @param Database        $database       Database connection manager
     * @param string          $apiPrefix      Base API prefix (default: "/v1")
     * @param UserController|null $userController Optional custom UserController
     * @param TaskController|null $taskController Optional custom TaskController
     * @param TaskController|null $taskController Optional custom TaskController
     * @param AuthMiddleware|null $authMiddleware Optional custom AuthMiddleware
     * @param CookieManager|null  $cookieManager  Optional custom CookieManager
     */
    public function __construct(
        Router $router,
        Logger $logger,
        Database $database,
        string $apiPrefix = '/v1',
        ?UserController $userController = null,
        ?TaskController $taskController = null,
        ?AuthMiddleware $authMiddleware = null,
        ?CookieManager $cookieManager = null
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->apiPrefix = rtrim($apiPrefix, '/');

        // Setup DB queries
        $pdo = $database->getConnection();
        $userQueries = new UserQueries($pdo);
        $taskQueries = new TaskQueries($pdo);
        $cookieManager = $cookieManager ?? new CookieManager();
        $jwt = new JwtService();

        // Middleware
        $this->authMiddleware = $authMiddleware ?? new AuthMiddleware($cookieManager, $jwt);

        // Controllers
        $this->userController = $userController ?? new UserController(
            new \App\Api\Auth\Service\DeleteUserService($userQueries, $cookieManager),
            new \App\Api\Auth\Service\GetUserService($userQueries),
            new \App\Api\Auth\Service\SigninService($userQueries, $cookieManager, $jwt),
            new \App\Api\Auth\Service\SignoutService($cookieManager),
            new \App\Api\Auth\Service\SignupService($userQueries, $cookieManager, $jwt),
            new \App\Api\Auth\Service\UpdateUserService($userQueries)
        );

        $this->taskController = $taskController ?? new TaskController(
            new \App\Api\Tasks\Service\AddTaskService($taskQueries),
            new \App\Api\Tasks\Service\DeleteTaskService($taskQueries),
            new \App\Api\Tasks\Service\UpdateTaskService($taskQueries),
            new \App\Api\Tasks\Service\MarkDoneTaskService($taskQueries),
            new \App\Api\Tasks\Service\GetTasksService($taskQueries)
        );

        // Register middlewares & routes
        $this->registerMiddlewares();
        $this->registerRoutes();
    }

    /**
     * Register global middlewares.
     *
     * Currently adds:
     * - JWT refresh middleware for all incoming requests
     *
     * Logs success or failure during token refresh.
     *
     * @return void
     */
    private function registerMiddlewares(): void
    {
        // Global middleware
        $this->router->addMiddleware(function (Request $req) {
            try {
                $this->authMiddleware->refreshJwt($req);
                $this->logger->info("JWT refresh executed for {$req->method} {$req->path}");
            } catch (Throwable $e) {
                $this->logger->error("JWT refresh failed: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Register API routes for users and tasks.
     *
     * - Applies authentication middleware to protected endpoints
     * - Maps each route to its respective controller handler
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        $authMiddlewareFn = [
            function (Request $req) {
                try {
                    $this->authMiddleware->requireAuth($req);
                    $this->logger->info("Auth passed for {$req->method} {$req->path}");
                } catch (Throwable $e) {
                    $this->logger->warning("Auth failed for {$req->method} {$req->path}: " . $e->getMessage());
                    throw $e;
                }
            }
        ];

        // User routes
        $userRoutes = [
            ['POST', '/users/signup', 'signup', []],
            ['POST', '/users/signin', 'signin', []],
            ['POST', '/users/signout', 'signout', []],
            ['PUT', '/users/update', 'updateUser', $authMiddlewareFn],
            ['DELETE', '/users/delete', 'deleteUser', $authMiddlewareFn],
            ['GET', '/users/me', 'getUser', $authMiddlewareFn],
        ];

        // Task routes
        $taskRoutes = [
            ['POST', '/tasks/add', 'addTask', $authMiddlewareFn],
            ['PUT', '/tasks/mark_done', 'markDoneTask', $authMiddlewareFn],
            ['PUT', '/tasks/update', 'updateTask', $authMiddlewareFn],
            ['DELETE', '/tasks/delete', 'deleteTask', $authMiddlewareFn],
            ['GET', '/tasks', 'getTasks', $authMiddlewareFn],
        ];

        // Register all routes in batch
        $this->registerRouteBatch($userRoutes, $this->userController);
        $this->registerRouteBatch($taskRoutes, $this->taskController);
    }

    /**
     * Register a batch of routes for a specific controller.
     *
     * - Automatically constructs full API path with prefix
     * - Logs each route registration and request handling
     * - Wraps route handlers in try/catch for robust error handling
     *
     * @param array<int, array{0:string,1:string,2:string,3:array<int, callable>}>  $routes     Array of route definitions [method, path, handler, middlewares]
     * @param object                                                                $controller Controller instance handling the route
     *
     * @return void
     */
    private function registerRouteBatch(array $routes, object $controller): void
    {
        foreach ($routes as [$method, $path, $handler, $middlewares]) {
            $fullPath = $this->apiPrefix . $path;

            // Register route handler with middleware
            $this->router->register($method, $fullPath, function (Request $req, bool $forTest = false) use ($controller, $handler, $fullPath) {
                try {
                    // Check JSON parse errors from Request
                    if ($req->getJsonError() !== null) {
                        $this->logger->warning("Invalid JSON body in {$req->method} {$req->path}: " . $req->getJsonError());
                    }

                    $this->logger->info("Route called: $fullPath -> " . get_class($controller) . "::$handler");

                    // Get request data and invoke controller method
                    $data = $controller->$handler($req, $forTest);
                    $this->logger->info("Response prepared for $fullPath");

                    return $data;
                } catch (Throwable $e) {
                    $this->logger->error("Exception in route $fullPath: " . $e->getMessage());
                    throw $e;
                }
            }, $middlewares);
        }
    }

    /**
     * Dispatch the request through the router.
     *
     * - Handles exceptions and logs them
     * - Returns JSON-formatted error response if an unhandled error occurs
     *
     * @param Request|null $request Optional request to dispatch (default: global)
     * @param bool         $forTest When true, prevents immediate output (for testing)
     *
     * @return array<string, mixed>|null JSON response array or null
     */
    public function dispatch(?Request $request = null, bool $forTest = false): ?array
    {
        try {
            /** @var array<string, mixed>|null $data */
            $data = $this->router->dispatch($request, $forTest);

            if ($data === null) {
                return null;
            }

            // Ensure all keys are strings for PHPStan
            return array_map(fn($v) => $v, $data);
        } catch (Throwable $e) {
            $this->logger->error("Unhandled exception during dispatch: " . $e->getMessage());

            /** @var array<string, mixed> $errorResponse */
            $errorResponse = JsonResponder::error('Internal server error', 'error', 500)
                ->send(false, $forTest);

            return $errorResponse;
        }
    }
}
