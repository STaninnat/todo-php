<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class DeleteTaskService
 *
 * Handles the logic for deleting a task from the system.
 *
 * - Validates required input fields (`id`, `user_id`)
 * - Deletes the specified task from the database
 * - Recalculates pagination to reflect the updated task count
 *
 * @package App\Api\Tasks\Service
 */
class DeleteTaskService
{
    /** @var TaskQueries Database handler for task operations */
    private TaskQueries $taskQueries;

    /**
     * Constructor
     *
     * Initializes the service with a {@see TaskQueries} instance for
     * performing task-related database operations.
     *
     * @param TaskQueries $taskQueries Service to interact with task database
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute the process of deleting a task.
     *
     * - Validates `id` and `user_id` parameters
     * - Deletes the corresponding task from the database
     * - Calculates total pages for pagination after deletion
     *
     * @param Request $req Request object containing task identifiers
     *
     * @return array{
     *     id: int,
     *     totalPages: int
     * } Returns deleted task ID and updated total page count
     *
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws RuntimeException If the task deletion operation fails
     */
    public function execute(Request $req): array
    {
        $id = RequestValidator::getIntParam($req, 'id', 'Task ID must be a numeric string.');
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');

        // Attempt to delete the task
        $result = $this->taskQueries->deleteTask($id, $userId);
        RequestValidator::ensureSuccess($result, 'delete task');

        // Calculate total pages
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        // Return deleted task ID and updated pagination info
        return [
            'id' => $id,
            'totalPages' => $totalPages,
        ];
    }
}
