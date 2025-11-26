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
 * Class UpdateTaskService
 *
 * Handles the logic for updating an existing task.
 *
 * - Validates required input fields (`id`, `title`, `user_id`)
 * - Ensures the task exists before updating
 * - Updates task record in the database
 * - Computes pagination data via {@see TaskPaginator}
 *
 * @package App\Api\Tasks\Service
 */
class UpdateTaskService
{
    /** @var TaskQueries Database handler for task operations */
    private TaskQueries $taskQueries;

    /**
     * Constructor
     *
     * Injects {@see TaskQueries} dependency for database operations.
     *
     * @param TaskQueries $taskQueries Service to interact with task database
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute the process of updating a task.
     *
     * - Validates required parameters (`id`, `title`, `user_id`, `is_done`)
     * - Checks that the task exists for the given user
     * - Updates the task record in the database
     * - Returns updated task data and total page count
     *
     * @param Request $req Request object containing task update data
     *
     * @return array{
     *     task: array<int|string, mixed>,
     *     totalPages: int
     * } Returns updated task data and pagination info
     *
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws RuntimeException If the task could not be found or updated
     */
    public function execute(Request $req): array
    {
        $title = RequestValidator::getString($req, 'title', 'Task title is required.');
        $description = ''; // optional
        if (isset($req->body['description']) && is_string($req->body['description'])) {
            $description = trim(strip_tags($req->body['description']));
        }

        $id = RequestValidator::getInt($req, 'id', 'Task ID must be a numeric string.');
        $userId = RequestValidator::getString($req, 'user_id', 'User ID is required.');
        $isDone = RequestValidator::getBool($req, 'is_done', 'Invalid status value.', true);

        // Verify that the task exists
        $taskResult = $this->taskQueries->getTaskByID($id, $userId);
        if (!$taskResult->success || !$taskResult->hasData()) {
            throw new RuntimeException('No task found.');
        }

        // Update task information
        $result = $this->taskQueries->updateTask($id, $title, $description, (bool)$isDone, $userId);
        RequestValidator::ensureSuccess($result, 'update task');

        // Calculate total pages for pagination
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        return [
            'task' => (array) $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
