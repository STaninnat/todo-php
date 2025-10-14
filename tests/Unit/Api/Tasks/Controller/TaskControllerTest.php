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
use Tests\Unit\Api\TestHelperTrait as ApiTestHelperTrait;
use RuntimeException;

/**
 * Class TaskControllerTest
 *
 * Unit tests for the TaskController class.
 *
 * This test suite verifies:
 * - Successful responses for each task operation (add, delete, update, mark done, get tasks)
 * - Proper structure of response data and success messages
 * - Exception handling when service layers throw runtime errors
 *
 * Uses PHPUnit mocks for all dependent services to isolate controller logic.
 *
 * @package Tests\Unit\Api\Tasks\Controller
 */
class TaskControllerTest extends TestCase
{
    /** @var AddTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked add service */
    private $addService;

    /** @var DeleteTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked delete service */
    private $deleteService;

    /** @var UpdateTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked update service */
    private $updateService;

    /** @var MarkDoneTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked mark-done service */
    private $markDoneService;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject Mocked get-tasks service */
    private $getTasksService;

    /** @var TaskController Controller under test */
    private TaskController $controller;

    /**
     * Sets up the test environment by mocking all dependent services
     * and injecting them into a new TaskController instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Create mocks for all Task service dependencies
        $this->addService = $this->createMock(AddTaskService::class);
        $this->deleteService = $this->createMock(DeleteTaskService::class);
        $this->updateService = $this->createMock(UpdateTaskService::class);
        $this->markDoneService = $this->createMock(MarkDoneTaskService::class);
        $this->getTasksService = $this->createMock(GetTasksService::class);

        // Instantiate controller with mocked dependencies
        $this->controller = new TaskController(
            $this->addService,
            $this->deleteService,
            $this->updateService,
            $this->markDoneService,
            $this->getTasksService
        );
    }

    // Use shared request-builder helper
    use ApiTestHelperTrait;

    /**
     * Test successful addition of a task.
     *
     * Ensures the controller returns a valid success response with
     * correct task data and pagination info.
     *
     * @return void
     */
    public function testAddTaskSuccess(): void
    {
        $taskData = ['task' => ['id' => 1, 'title' => 'Task'], 'totalPages' => 2];
        $this->addService->method('execute')->willReturn($taskData);

        $req = $this->makeRequest(['title' => 'Task']);
        $decoded = $this->controller->addTask($req, true);

        // ✅ Assert successful creation
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task added successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test successful deletion of a task.
     *
     * Verifies expected response structure and values.
     *
     * @return void
     */
    public function testDeleteTaskSuccess(): void
    {
        $taskData = ['id' => 1, 'totalPages' => 3];
        $this->deleteService->method('execute')->willReturn($taskData);

        // Build DELETE request with task id in both body & params
        $req = $this->makeRequest(
            body: ['id' => 1],
            params: ['id' => 1],
            method: 'DELETE',
            path: '/tasks/1'
        );
        $decoded = $this->controller->deleteTask($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task deleted successfully', $decoded['message']);
        $this->assertSame($taskData['id'], $decoded['data']['id']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test successful update of a task.
     *
     * Confirms that updated task data and pagination values
     * are correctly returned by the controller.
     *
     * @return void
     */
    public function testUpdateTaskSuccess(): void
    {
        $taskData = ['task' => ['id' => 1, 'title' => 'Updated'], 'totalPages' => 4];
        $this->updateService->method('execute')->willReturn($taskData);

        // PUT request simulating update
        $req = $this->makeRequest(
            body: ['title' => 'Updated'],
            params: ['id' => 1],
            method: 'PUT',
            path: '/tasks/1'
        );
        $decoded = $this->controller->updateTask($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task updated successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test successful marking of a task as done.
     *
     * Validates that status update and pagination fields are returned.
     *
     * @return void
     */
    public function testMarkDoneTaskSuccess(): void
    {
        $taskData = ['task' => ['id' => 1, 'is_done' => true], 'totalPages' => 5];
        $this->markDoneService->method('execute')->willReturn($taskData);

        $req = $this->makeRequest(
            body: ['is_done' => 1],
            params: ['id' => 1],
            method: 'PATCH',
            path: '/tasks/1/done'
        );
        $decoded = $this->controller->markDoneTask($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task status updated successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test successful retrieval of tasks list.
     *
     * Ensures controller returns proper structure and pagination info.
     *
     * @return void
     */
    public function testGetTasksSuccess(): void
    {
        $taskData = ['task' => [['id' => 1, 'title' => 'Task']], 'totalPages' => 2];
        $this->getTasksService->method('execute')->willReturn($taskData);

        $req = $this->makeRequest(
            query: ['page' => 1],
            params: ['user_id' => 123],
            method: 'GET',
            path: '/tasks'
        );
        $decoded = $this->controller->getTasks($req, true);

        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('Task retrieved successfully', $decoded['message']);
        $this->assertSame($taskData['task'], $decoded['data']['task']);
        $this->assertSame($taskData['totalPages'], $decoded['totalPages']);
    }

    /**
     * Test that addTask() properly propagates RuntimeException.
     *
     * @return void
     */
    public function testAddTaskThrowsException(): void
    {
        $this->addService->method('execute')->willThrowException(new RuntimeException('Add failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(['title' => 'Task']);
        $this->controller->addTask($req, true);
    }

    /**
     * Test that deleteTask() properly propagates RuntimeException.
     *
     * @return void
     */
    public function testDeleteTaskThrowsException(): void
    {
        $this->deleteService->method('execute')->willThrowException(new RuntimeException('Delete failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'DELETE', path: '/tasks/1');
        $this->controller->deleteTask($req, true);
    }

    /**
     * Test that updateTask() properly propagates RuntimeException.
     *
     * @return void
     */
    public function testUpdateTaskThrowsException(): void
    {
        $this->updateService->method('execute')->willThrowException(new RuntimeException('Update failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'PUT', path: '/tasks/1');
        $this->controller->updateTask($req, true);
    }

    /**
     * Test that markDoneTask() properly propagates RuntimeException.
     *
     * @return void
     */
    public function testMarkDoneTaskThrowsException(): void
    {
        $this->markDoneService->method('execute')->willThrowException(new RuntimeException('MarkDone failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(params: ['id' => 1], method: 'PATCH', path: '/tasks/1/done');
        $this->controller->markDoneTask($req, true);
    }

    /**
     * Test that getTasks() properly propagates RuntimeException.
     *
     * @return void
     */
    public function testGetTasksThrowsException(): void
    {
        $this->getTasksService->method('execute')->willThrowException(new RuntimeException('GetTasks failed'));
        $this->expectException(RuntimeException::class);

        $req = $this->makeRequest(query: ['page' => 1], method: 'GET', path: '/tasks');
        $this->controller->getTasks($req, true);
    }
}
