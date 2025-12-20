<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Api\RouterApp;
use App\Api\Router;
use App\Utils\Logger;
use App\DB\Database;
use App\Api\Auth\Controller\UserController;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Middlewares\AuthMiddleware;
use App\Api\Request;
use PDO;

/**
 * Class RouterAppUnitTest
 *
 * This test suite validates the initialization and dispatching logic of the central
 * RouterApp class. It uses mocks for all dependencies (Router, Logger, Database,
 * Controllers, Middleware) to ensure unit isolation.
 *
 * Scenarios covered:
 * - Proper instantiation and dependency injection.
 * - Correct dispatching of requests to the UserController.
 * - Correct dispatching of requests to the TaskController.
 *
 * @package Tests\Unit\Api
 */
class RouterAppUnitTest extends TestCase
{
    /** @var RouterApp Instance under test */
    private RouterApp $routerApp;

    /** @var Router Core router */
    private $router;

    /** @var Logger&MockObject Logger mock */
    private $logger;

    /** @var Database&MockObject Database mock */
    private $database;

    /** @var UserController&MockObject UserController mock */
    private $userController;

    /** @var TaskController&MockObject TaskController mock */
    private $taskController;

    /** @var AuthMiddleware&MockObject AuthMiddleware mock */
    private $authMiddleware;

    /**
     * Setup test environment.
     *
     * Initializes mocks and the RouterApp instance.
     * Sets JWT_SECRET to ensure JWT services assume a valid environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_ENV['JWT_SECRET'] = 'test-secret'; // Required for JwtService
        $this->router = new Router(); // Use real router for simplicity in dispatching
        $this->logger = $this->createMock(Logger::class);
        $this->database = $this->createMock(Database::class);
        $this->database->method('getConnection')->willReturn($this->createMock(PDO::class));

        $this->userController = $this->createMock(UserController::class);
        $this->taskController = $this->createMock(TaskController::class);
        $this->authMiddleware = $this->createMock(AuthMiddleware::class);

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
     * Test that RouterApp instantiates correctly.
     *
     * @return void
     */
    public function testInitialization(): void
    {
        $this->assertInstanceOf(RouterApp::class, $this->routerApp);
    }

    /**
     * Clean up test environment.
     *
     * Unsets the JWT_SECRET environment variable to prevent pollution of other tests
     * (e.g., JwtServiceUnitTest) that may rely on this variable being missing.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($_ENV['JWT_SECRET']);
        parent::tearDown();
    }

    /**
     * Test successful dispatch to UserController.
     *
     * Verifies that the router delegates the request to the correct controller method
     * based on route registration.
     *
     * @return void
     */
    public function testDispatchToUserController(): void
    {
        $req = new Request('POST', '/v1/users/signin');

        $this->userController->expects($this->once())
            ->method('signin')
            ->willReturn(['success' => true]);

        $res = $this->routerApp->dispatch($req, true);

        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
    }

    /**
     * Test successful dispatch to TaskController.
     *
     * Verifies that task-related requests are correctly routed.
     *
     * @return void
     */
    public function testDispatchToTaskController(): void
    {
        $req = new Request('GET', '/v1/tasks');

        $this->taskController->expects($this->once())
            ->method('getTasks')
            ->willReturn(['success' => true]);

        $res = $this->routerApp->dispatch($req, true);

        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
    }
}
