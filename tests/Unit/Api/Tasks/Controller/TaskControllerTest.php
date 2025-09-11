<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Controller;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Tasks\Service\AddTaskService;
use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Tasks\Service\UpdateTaskService;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\Api\Tasks\Service\GetTasksService;
use RuntimeException;

/**
 * Class TaskControllerTest
 *
 * Unit tests for the TaskController class.
 *
 * This test suite verifies:
 * - addTask(), deleteTask(), updateTask(), markDoneTask(), getTasks() success scenarios
 * - Exception handling for service failures
 *
 * All service dependencies are mocked to isolate controller logic.
 *
 * @package Tests\Unit\Api\Tasks\Controller
 */
class TaskControllerTest extends TestCase
{
    /** @var AddTaskService&\PHPUnit\Framework\MockObject\MockObject */
    private $addService;

    /** @var DeleteTaskService&\PHPUnit\Framework\MockObject\MockObject */
    private $deleteService;

    /** @var UpdateTaskService&\PHPUnit\Framework\MockObject\MockObject */
    private $updateService;

    /** @var MarkDoneTaskService&\PHPUnit\Framework\MockObject\MockObject */
    private $markDoneService;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject */
    private $getTasksService;

    private TaskController $controller;

    /**
     * Setup mocks and controller instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create mock services
        $this->addService = $this->createMock(AddTaskService::class);
        $this->deleteService = $this->createMock(DeleteTaskService::class);
        $this->updateService = $this->createMock(UpdateTaskService::class);
        $this->markDoneService = $this->createMock(MarkDoneTaskService::class);
        $this->getTasksService = $this->createMock(GetTasksService::class);

        // Instantiate controller with mocked services
        $this->controller = new TaskController(
            $this->addService,
            $this->deleteService,
            $this->updateService,
            $this->markDoneService,
            $this->getTasksService
        );
    }

    // ------------------------------
    // Success cases with $forTest = true
    // ------------------------------

    /**
     * Test: addTask() success scenario
     *
     * @return void
     */
    public function testAddTaskSuccess(): void
    {
        // Prepare fake task data returned by service
        $taskData = ['task' => ['id' => 1, 'title' => 'Task'], 'totalPages' => 2];
        $this->addService->method('execute')->willReturn($taskData);

        // Call controller method
        $decoded = $this->controller->addTask(['title' => 'Task'], true);

        // Assert response structure and content
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task added successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test: deleteTask() success scenario
     *
     * @return void
     */
    public function testDeleteTaskSuccess(): void
    {
        $taskData = ['id' => 1, 'totalPages' => 3];
        $this->deleteService->method('execute')->willReturn($taskData);

        $decoded = $this->controller->deleteTask(['id' => 1], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task deleted successfully', $decoded['message']);
        $this->assertSame($taskData['id'], $decoded['data']['id']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test: updateTask() success scenario
     *
     * @return void
     */
    public function testUpdateTaskSuccess(): void
    {
        $taskData = ['task' => ['id' => 1, 'title' => 'Updated'], 'totalPages' => 4];
        $this->updateService->method('execute')->willReturn($taskData);

        $decoded = $this->controller->updateTask(['id' => 1, 'title' => 'Updated'], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task updated successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test: markDoneTask() success scenario
     *
     * @return void
     */
    public function testMarkDoneTaskSuccess(): void
    {
        $taskData = ['task' => ['id' => 1, 'is_done' => true], 'totalPages' => 5];
        $this->markDoneService->method('execute')->willReturn($taskData);

        $decoded = $this->controller->markDoneTask(['id' => 1, 'is_done' => 1], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task status updated successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test: getTasks() success scenario
     *
     * @return void
     */
    public function testGetTasksSuccess(): void
    {
        $taskData = ['task' => [['id' => 1, 'title' => 'Task']], 'totalPages' => 2];
        $this->getTasksService->method('execute')->willReturn($taskData);

        $decoded = $this->controller->getTasks(['user_id' => 123], true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task retrieved successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    // ------------------------------
    // Exception cases
    // ------------------------------

    /**
     * Test: addTask() throws RuntimeException
     *
     * @return void
     */
    public function testAddTaskThrowsException(): void
    {
        // Mock service to throw exception
        $this->addService->method('execute')->willThrowException(new RuntimeException('Add failed'));

        $this->expectException(RuntimeException::class);

        // Call controller, should throw
        $this->controller->addTask(['title' => 'Task'], true);
    }

    /**
     * Test: deleteTask() throws RuntimeException
     *
     * @return void
     */
    public function testDeleteTaskThrowsException(): void
    {
        $this->deleteService->method('execute')->willThrowException(new RuntimeException('Delete failed'));
        $this->expectException(RuntimeException::class);
        $this->controller->deleteTask(['id' => 1], true);
    }

    /**
     * Test: updateTask() throws RuntimeException
     *
     * @return void
     */
    public function testUpdateTaskThrowsException(): void
    {
        $this->updateService->method('execute')->willThrowException(new RuntimeException('Update failed'));
        $this->expectException(RuntimeException::class);
        $this->controller->updateTask(['id' => 1, 'title' => 'Updated'], true);
    }

    /**
     * Test: markDoneTask() throws RuntimeException
     *
     * @return void
     */
    public function testMarkDoneTaskThrowsException(): void
    {
        $this->markDoneService->method('execute')->willThrowException(new RuntimeException('MarkDone failed'));
        $this->expectException(RuntimeException::class);
        $this->controller->markDoneTask(['id' => 1, 'is_done' => 1], true);
    }

    /**
     * Test: getTasks() throws RuntimeException
     *
     * @return void
     */
    public function testGetTasksThrowsException(): void
    {
        $this->getTasksService->method('execute')->willThrowException(new RuntimeException('GetTasks failed'));
        $this->expectException(RuntimeException::class);
        $this->controller->getTasks(['user_id' => 123], true);
    }
}
