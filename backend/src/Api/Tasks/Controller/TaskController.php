<?php

declare(strict_types=1);

namespace App\Api\Tasks\Controller;

use App\Api\Request;
use App\Api\Tasks\Service\AddTaskService;
use App\Api\Tasks\Service\DeleteTaskService;
use App\Api\Tasks\Service\UpdateTaskService;
use App\Api\Tasks\Service\MarkDoneTaskService;
use App\Api\Tasks\Service\GetTasksService;
use App\Utils\JsonResponder;

/**
 * Class TaskController
 *
 * Controller responsible for handling all task-related endpoints:
 * - Add new task
 * - Delete existing task
 * - Update task details
 * - Mark task as done
 * - Retrieve task list
 *
 * Each method delegates its business logic to a corresponding service
 * and returns standardized JSON responses through {@see JsonResponder}.
 *
 * @package App\Api\Tasks\Controller
 */
class TaskController
{
    /** @var AddTaskService Handles adding a new task */
    private AddTaskService $addService;

    /** @var DeleteTaskService Handles deleting a task */
    private DeleteTaskService $deleteService;

    /** @var UpdateTaskService Handles updating an existing task */
    private UpdateTaskService $updateService;

    /** @var MarkDoneTaskService Handles marking a task as completed */
    private MarkDoneTaskService $markDoneService;

    /** @var GetTasksService Handles retrieving tasks */
    private GetTasksService $getTasksService;

    /**
     * Constructor
     *
     * Injects service dependencies responsible for task management.
     *
     * @param AddTaskService      $addService      Service to add a task.
     * @param DeleteTaskService   $deleteService   Service to delete a task.
     * @param UpdateTaskService   $updateService   Service to update a task.
     * @param MarkDoneTaskService $markDoneService Service to mark task as done.
     * @param GetTasksService     $getTasksService Service to retrieve tasks.
     */
    public function __construct(
        AddTaskService $addService,
        DeleteTaskService $deleteService,
        UpdateTaskService $updateService,
        MarkDoneTaskService $markDoneService,
        GetTasksService $getTasksService
    ) {
        $this->addService = $addService;
        $this->deleteService = $deleteService;
        $this->updateService = $updateService;
        $this->markDoneService = $markDoneService;
        $this->getTasksService = $getTasksService;
    }

    /**
     * Handle creation of a new task.
     *
     * @param Request $req      The HTTP request containing task data.
     * @param bool    $forTest  Whether to return response array (for testing).
     *
     * @return array<string, mixed>|null JSON response array if testing mode, otherwise null.
     */
    public function addTask(Request $req, bool $forTest = false): ?array
    {
        // Delegate to service
        $data = $this->addService->execute($req);

        // Build JSON response
        $response = JsonResponder::success('Task added successfully')
            ->withPayload(['task' => $data['task']])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle deletion of a task by ID.
     *
     * @param Request $req      The HTTP request containing task ID.
     * @param bool    $forTest  Whether to return response array (for testing).
     *
     * @return array<string, mixed>|null JSON response array if testing mode, otherwise null.
     */
    public function deleteTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->deleteService->execute($req);

        $response = JsonResponder::success('Task deleted successfully')
            ->withPayload(['id' => $data['id']])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle updating of a task's data.
     *
     * @param Request $req      The HTTP request containing updated task info.
     * @param bool    $forTest  Whether to return response array (for testing).
     *
     * @return array<string, mixed>|null JSON response array if testing mode, otherwise null.
     */
    public function updateTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->updateService->execute($req);

        $response = JsonResponder::success('Task updated successfully')
            ->withPayload(['task' => $data['task']])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle marking a task as done.
     *
     * @param Request $req      The HTTP request containing task ID.
     * @param bool    $forTest  Whether to return response array (for testing).
     *
     * @return array<string, mixed>|null JSON response array if testing mode, otherwise null.
     */
    public function markDoneTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->markDoneService->execute($req);

        $response = JsonResponder::success('Task status updated successfully')
            ->withPayload(['task' => $data['task']])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Handle retrieval of task list.
     *
     * @param Request $req      The HTTP request containing filter/pagination info.
     * @param bool    $forTest  Whether to return response array (for testing).
     *
     * @return array<string, mixed>|null JSON response array if testing mode, otherwise null.
     */
    public function getTasks(Request $req, bool $forTest = false): ?array
    {
        $data = $this->getTasksService->execute($req);

        $response = JsonResponder::success('Task retrieved successfully')
            ->withPayload(['task' => $data['task']])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
