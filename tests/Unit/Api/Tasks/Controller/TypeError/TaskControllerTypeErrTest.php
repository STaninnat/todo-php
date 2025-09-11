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

/**
 * Class TaskControllerTypeErrTest
 *
 * Unit tests for TaskController to verify TypeError handling.
 *
 * This test suite ensures that passing invalid argument types
 * to controller methods triggers a TypeError as expected.
 *
 * @package Tests\Unit\Api\Tasks\Controller\TypeError
 */
class TaskControllerTypeErrTest extends TestCase
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

    /**
     * Test: addTask() triggers TypeError for invalid argument type.
     *
     * @return void
     */
    public function testAddTaskTypeError(): void
    {
        // Expect TypeError when passing string instead of array
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->addTask('invalid type', true);
    }

    /**
     * Test: deleteTask() triggers TypeError for invalid argument type.
     *
     * @return void
     */
    public function testDeleteTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->deleteTask('invalid type', true);
    }

    /**
     * Test: updateTask() triggers TypeError for invalid argument type.
     *
     * @return void
     */
    public function testUpdateTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->updateTask('invalid type', true);
    }

    /**
     * Test: markDoneTask() triggers TypeError for invalid argument type.
     *
     * @return void
     */
    public function testMarkDoneTaskTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->markDoneTask('invalid type', true);
    }

    /**
     * Test: getTasks() triggers TypeError for invalid argument type.
     *
     * @return void
     */
    public function testGetTasksTypeError(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line */
        $this->controller->getTasks('invalid type', true);
    }
}
