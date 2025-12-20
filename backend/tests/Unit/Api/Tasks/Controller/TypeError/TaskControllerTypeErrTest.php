<?php

declare(strict_types=1);

namespace Tests\Unit\Api\Tasks\Controller\TypeError;

use PHPUnit\Framework\TestCase;
use App\Api\Tasks\Controller\TaskController;
use App\Api\Tasks\Service\AddTaskService;
use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Tasks\Service\UpdateTaskService;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\Api\Tasks\Service\GetTasksService;
use App\Api\Tasks\Service\BulkDeleteTaskService;
use App\Api\Tasks\Service\BulkMarkDoneTaskService;

/**
 * Class TaskControllerTypeErrTest
 *
 * Unit tests for TaskController to verify TypeError handling.
 *
 * Ensures that passing arguments with invalid types to controller
 * methods raises a TypeError as expected — protecting against
 * incorrect usage and enforcing type safety.
 *
 * @package Tests\Unit\Api\Tasks\Controller\TypeError
 */
class TaskControllerTypeErrTest extends TestCase
{
    /** @var AddTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked AddTaskService */
    private $addService;

    /** @var DeleteTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked DeleteTaskService */
    private $deleteService;

    /** @var UpdateTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked UpdateTaskService */
    private $updateService;

    /** @var MarkDoneTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked MarkDoneTaskService */
    private $markDoneService;

    /** @var GetTasksService&\PHPUnit\Framework\MockObject\MockObject Mocked GetTasksService */
    private $getTasksService;

    /** @var BulkDeleteTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked BulkDeleteTaskService */
    private $bulkDeleteService;

    /** @var BulkMarkDoneTaskService&\PHPUnit\Framework\MockObject\MockObject Mocked BulkMarkDoneTaskService */
    private $bulkMarkDoneService;

    /** @var TaskController Controller instance under test */
    private TaskController $controller;

    /**
     * Setup the testing environment before each test.
     *
     * Creates mock instances for all dependent services and
     * injects them into a fresh TaskController instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Initialize mocked services to isolate controller logic
        $this->addService = $this->createMock(AddTaskService::class);
        $this->deleteService = $this->createMock(DeleteTaskService::class);
        $this->updateService = $this->createMock(UpdateTaskService::class);
        $this->markDoneService = $this->createMock(MarkDoneTaskService::class);
        $this->getTasksService = $this->createMock(GetTasksService::class);
        $this->bulkDeleteService = $this->createMock(BulkDeleteTaskService::class);
        $this->bulkMarkDoneService = $this->createMock(BulkMarkDoneTaskService::class);

        // Inject mocks into the controller
        $this->controller = new TaskController(
            $this->addService,
            $this->deleteService,
            $this->updateService,
            $this->markDoneService,
            $this->getTasksService,
            $this->bulkDeleteService,
            $this->bulkMarkDoneService
        );
    }

    /**
     * Test that addTask() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testAddTaskTypeError(): void
    {
        // Expect a TypeError when a string is passed instead of Request
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Intentionally passing invalid type for test */
        $this->controller->addTask('invalid type', true);
    }

    /**
     * Test that deleteTask() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testDeleteTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Prevent static analysis error for invalid argument */
        $this->controller->deleteTask('invalid type', true);
    }

    /**
     * Test that updateTask() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testUpdateTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Intentionally invalid to test type safety */
        $this->controller->updateTask('invalid type', true);
    }

    /**
     * Test that markDoneTask() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testMarkDoneTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Ensures strict type enforcement */
        $this->controller->markDoneTask('invalid type', true);
    }

    /**
     * Test that getTasks() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testGetTasksTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Invalid type to confirm strict parameter expectations */
        $this->controller->getTasks('invalid type', true);
    }

    /**
     * Test that deleteTasksBulk() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testDeleteTasksBulkTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->deleteTasksBulk('invalid type', true);
    }

    /**
     * Test that markDoneTasksBulk() throws a TypeError when receiving an invalid argument type.
     *
     * @return void
     */
    public function testMarkDoneTasksBulkTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->markDoneTasksBulk('invalid type', true);
    }
}
