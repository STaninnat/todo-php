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

class RouterApp
{
    private Router $router;
    private Logger $logger;

    private string $apiPrefix;

    private UserController $userController;
    private TaskController $taskController;
    private AuthMiddleware $authMiddleware;

    public function __construct(
        Router $router,
        Logger $logger,
        Database $database,
        string $apiPrefix = '/v1',
        ?UserController $userController = null,
        ?TaskController $taskController = null,
        ?AuthMiddleware $authMiddleware = null
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->apiPrefix = rtrim($apiPrefix, '/');

        // Setup DB queries
        $pdo = $database->getConnection();
        $userQueries = new UserQueries($pdo);
        $taskQueries = new TaskQueries($pdo);
        $cookieManager = new CookieManager();
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

    private function registerMiddlewares(): void
    {
        // Global middleware
        $this->router->addMiddleware(function ($req) {
            try {
                $this->authMiddleware->refreshJwt($req);
                $this->logger->info("JWT refresh executed for {$req->method} {$req->path}");
            } catch (\Throwable $e) {
                $this->logger->error("JWT refresh failed: " . $e->getMessage());
                throw $e;
            }
        });
    }

    private function registerRoutes(): void
    {
        $authMiddlewareFn = [
            function ($req) {
                try {
                    $this->authMiddleware->requireAuth($req);
                    $this->logger->info("Auth passed for {$req->method} {$req->path}");
                } catch (\Throwable $e) {
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

        $this->registerRouteBatch($userRoutes, $this->userController);
        $this->registerRouteBatch($taskRoutes, $this->taskController);
    }

    private function registerRouteBatch(array $routes, object $controller): void
    {
        $getRequestData = fn($req) => match ($req->method) {
            'GET' => $req->query,
            default => $req->body
        };

        foreach ($routes as [$method, $path, $handler, $middlewares]) {
            $fullPath = $this->apiPrefix . $path;

            $this->router->register($method, $fullPath, function ($req) use ($controller, $handler, $fullPath, $getRequestData) {
                try {
                    $this->logger->info("Route called: $fullPath -> " . get_class($controller) . "::$handler");
                    $data = $controller->$handler($getRequestData($req));
                    $this->logger->info("Response prepared for $fullPath");
                    return $data;
                } catch (\Throwable $e) {
                    $this->logger->error("Exception in route $fullPath: " . $e->getMessage());
                    throw $e;
                }
            }, $middlewares);
        }
    }

    public function dispatch(?Request $request = null, bool $forTest = false): ?array
    {
        try {
            return $this->router->dispatch($request, $forTest);
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception during dispatch: " . $e->getMessage());
            return JsonResponder::error('Internal server error', 'error', 500)
                ->send(false, $forTest);
        }
    }
}
