<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

class UpdateTaskService
{
    private TaskQueries $taskQueries;

    /**
     * Constructor
     *
     * Injects the TaskQueries dependency for database operations.
     *
     * @param TaskQueries $taskQueries Service to interact with task database.
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute the task update process.
     *
     * @param Request $req Request object containing task update data.
     *
     * @return array Array containing updated task data and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing or invalid.
     * @throws RuntimeException If the task could not be found or updated.
     */
    public function execute(Request $req): array
    {
        $title = RequestValidator::getStringParam($req, 'title', 'Task title is required.');
        $description = trim(strip_tags($req->body['description'] ?? ''));   // optional
        $id = RequestValidator::getIntParam($req, 'id', 'Task ID must be a numeric string.');
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');
        $isDone = RequestValidator::getBoolParam($req, 'is_done', 'Invalid status value.');

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
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
