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
 * Controller responsible for handling task-related operations such as
 * addition, deletion, update, marking as done, and retrieval.
 *
 * Each method interacts with its corresponding service to process business
 * logic and returns standardized JSON responses via JsonResponder.
 *
 * @package App\Api\Tasks\Controller
 */
class TaskController
{
    private AddTaskService $addService;
    private DeleteTaskService $deleteService;
    private UpdateTaskService $updateService;
    private MarkDoneTaskService $markDoneService;
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

    public function addTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->addService->execute($req);

        $response = JsonResponder::success('Task added successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    public function deleteTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->deleteService->execute($req);

        $response = JsonResponder::success('Task deleted successfully')
            ->withPayload(['id' => $data['id']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    public function updateTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->updateService->execute($req);

        $response = JsonResponder::success('Task updated successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    public function markDoneTask(Request $req, bool $forTest = false): ?array
    {
        $data = $this->markDoneService->execute($req);

        $response = JsonResponder::success('Task status updated successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    public function getTasks(Request $req, bool $forTest = false): ?array
    {
        $data = $this->getTasksService->execute($req);

        $response = JsonResponder::success('Task retrieved successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
