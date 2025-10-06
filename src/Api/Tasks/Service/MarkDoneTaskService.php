<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

class MarkDoneTaskService
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
     * Execute the process to mark a task as done or undone.
     *
     * @param Request $req
     * 
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function execute(Request $req): array
    {
        $id = RequestValidator::getIntParam($req, 'id', 'Task ID must be a numeric string.');
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');
        $isDone = RequestValidator::getBoolParam($req, 'is_done', 'Invalid status value.');

        // Verify that the task exists
        $taskResult = $this->taskQueries->getTaskByID($id, $userId);
        if (!$taskResult->success || !$taskResult->hasData()) {
            throw new RuntimeException('No task found.');
        }

        // Update task completion status
        $result = $this->taskQueries->markDone($id, (bool) $isDone, $userId);
        RequestValidator::ensureSuccess($result, 'mark task as done');

        // Calculate total pages
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
