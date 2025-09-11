<?php

declare(strict_types=1);

namespace App\Api\Tasks\Controller;

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

    /**
     * Add a new task.
     *
     * @param array $input   Input data for creating a new task.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with added task in test mode, otherwise null.
     */
    public function addTask(array $input, bool $forTest = false): ?array
    {
        // Execute task addition
        $data = $this->addService->execute($input);

        // Build response with payload and total pages
        $response = JsonResponder::success('Task added successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Delete an existing task.
     *
     * @param array $input   Input data containing task identification.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with deleted task id in test mode, otherwise null.
     */
    public function deleteTask(array $input, bool $forTest = false): ?array
    {
        // Execute task deletion
        $data = $this->deleteService->execute($input);

        // Build response with payload and total pages
        $response = JsonResponder::success('Task deleted successfully')
            ->withPayload(['id' => $data['id']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Update an existing task.
     *
     * @param array $input   Input data containing updated task information.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with updated task in test mode, otherwise null.
     */
    public function updateTask(array $input, bool $forTest = false): ?array
    {
        // Execute task update
        $data = $this->updateService->execute($input);

        // Build response with payload and total pages
        $response = JsonResponder::success('Task updated successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Mark a task as done.
     *
     * @param array $input   Input data containing task identification.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with updated task in test mode, otherwise null.
     */
    public function markDoneTask(array $input, bool $forTest = false): ?array
    {
        // Execute mark as done
        $data = $this->markDoneService->execute($input);

        // Build response with payload and total pages
        $response = JsonResponder::success('Task status updated successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }

    /**
     * Retrieve tasks.
     *
     * @param array $input   Input data for fetching tasks.
     * @param bool  $forTest If true, return the response instead of sending it.
     *
     * @return array|null JSON response with task list in test mode, otherwise null.
     */
    public function getTasks(array $input, bool $forTest = false): ?array
    {
        // Execute tasks retrieval
        $data = $this->getTasksService->execute($input);

        // Build response with payload and total pages
        $response = JsonResponder::success('Task retrieved successfully')
            ->withPayload(['task' => $data['task']])
            ->withTotalPages($data['totalPages'])
            ->send(!$forTest, $forTest);

        return $forTest ? $response : null;
    }
}
