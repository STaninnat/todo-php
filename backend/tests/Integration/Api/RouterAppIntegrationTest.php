<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use App\Api\Request;
use App\Api\Router;
use App\Api\RouterApp;
use App\Api\Auth\Controller\UserController;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Middlewares\AuthMiddleware;
use App\DB\Database;
use App\Utils\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RouterAppIntegrationTest
 *
 * Integration tests for {@see RouterApp}, validating interaction between
 * routing, controllers, middlewares, request handling, and error flow.
 *
 * Covers:
 * - Application initialization
 * - Successful request dispatching
 * - Route not found handling
 * - Exception handling during controller execution
 * - Middleware execution for protected routes
 *
 * @package Tests\Integration\Api
 */
class RouterAppIntegrationTest extends TestCase
{
    /** @var RouterApp Application instance under test */
    private RouterApp $routerApp;

    /** @var Router Main router responsible for resolving routes */
    private Router $router;

    /** @var Logger|MockObject Logger mock for suppressing real output */
    private Logger|MockObject $logger;

    /** @var Database|MockObject Database mock used for controller dependencies */
    private Database|MockObject $database;

    /** @var UserController|MockObject Mocked user controller */
    private UserController|MockObject $userController;

    /** @var TaskController|MockObject Mocked task controller */
    private TaskController|MockObject $taskController;

    /** @var AuthMiddleware|MockObject Mocked authentication middleware */
    private AuthMiddleware|MockObject $authMiddleware;

    /**
     * Setup test environment.
     *
     * Initializes router, mocked dependencies, and constructs RouterApp
     * with a base prefix `/v1`. PDO is mocked to avoid real DB access.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router();
        $this->logger = $this->createMock(Logger::class);
        $this->database = $this->createMock(Database::class);
        $this->database->method('getConnection')->willReturn($this->createMock(\PDO::class));

        $this->userController = $this->createMock(UserController::class);
        $this->taskController = $this->createMock(TaskController::class);
        $this->authMiddleware = $this->createMock(AuthMiddleware::class);

        // Create RouterApp instance
        $this->routerApp = new RouterApp(
            $this->router,
            $this->logger,
            $this->database,
            '/v1',
            $this->userController,
            $this->taskController,
            $this->authMiddleware
        );
    }

    /**
     * Ensure RouterApp initializes correctly with all dependencies.
     *
     * @return void
     */
    public function testAppInitialization(): void
    {
        $this->assertInstanceOf(RouterApp::class, $this->routerApp);
    }

    /**
     * Validate successful request dispatching for an existing route.
     *
     * - Mocks UserController::signin()
     * - Ensures returned array matches expected structure
     *
     * @return void
     */
    public function testDispatchSuccess(): void
    {
        // Setup expectation for UserController
        /** @phpstan-ignore method.notFound */
        $this->userController->expects($this->once())
            ->method('signin')
            ->willReturn(['success' => true, 'message' => 'Mocked login']);

        $request = new Request('POST', '/v1/users/signin');
        $response = $this->routerApp->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame('Mocked login', $response['message']);
    }

    /**
     * Validate that a non-existing route returns a standardized not-found error.
     *
     * @return void
     */
    public function testDispatchNotFound(): void
    {
        $request = new Request('GET', '/v1/not-found');
        $response = $this->routerApp->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertIsString($response['message']);
        $this->assertStringContainsString('Route not found', $response['message']);
    }

    /**
     * Validate handling of uncaught exceptions thrown by controllers.
     *
     * - Mocks UserController::signin() to throw Error
     * - Should return a standardized "Internal server error" response
     *
     * @return void
     */
    public function testDispatchError(): void
    {
        // Setup expectation for UserController to throw exception
        /** @phpstan-ignore method.notFound */
        $this->userController->expects($this->once())
            ->method('signin')
            ->willThrowException(new \Error('Critical failure'));

        $request = new Request('POST', '/v1/users/signin');
        $response = $this->routerApp->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('error', $response['type']);
        $this->assertSame('Internal server error', $response['message']);
    }

    /**
     * Ensure middlewares execute correctly for protected routes.
     *
     * - Expects AuthMiddleware::requireAuth() to be invoked once
     * - Validates successful controller result after auth passes
     *
     * @return void
     */
    public function testMiddlewareExecution(): void
    {
        // Expect auth middleware to be called for protected route
        /** @phpstan-ignore method.notFound */
        $this->authMiddleware->expects($this->once())
            ->method('requireAuth');

        /** @phpstan-ignore method.notFound */
        $this->userController->expects($this->once())
            ->method('getUser')
            ->willReturn(['success' => true, 'data' => ['user' => 'me']]);

        $request = new Request('GET', '/v1/users/me');
        $response = $this->routerApp->dispatch($request, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
    }
}
