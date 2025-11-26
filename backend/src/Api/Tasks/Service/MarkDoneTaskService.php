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
 * Class MarkDoneTaskService
 *
 * Service responsible for marking a task as done or undone.
 *
 * - Validates input parameters (`id`, `user_id`, `is_done`)
 * - Ensures the task exists before updating
 * - Updates task completion status in the database
 * - Returns updated task data and pagination info
 *
 * @package App\Api\Tasks\Service
 */
class MarkDoneTaskService
{
    /** @var TaskQueries Database handler for task operations */
    private TaskQueries $taskQueries;

    /**
     * Constructor
     *
     * Initializes the service with a {@see TaskQueries} instance for
     * performing database operations.
     *
     * @param TaskQueries $taskQueries Service to interact with task database
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute the process of marking a task as done or undone.
     *
     * - Validates `id`, `user_id`, and `is_done` from the request
     * - Checks that the task exists
     * - Updates completion status in the database
     * - Returns task data along with updated pagination info
     *
     * @param Request $req Incoming request containing task ID and status
     *
     * @return array{
     *     task: array<int|string, mixed>,
     *     totalPages: int
     * } Returns updated task data and total page count
     *
     * @throws InvalidArgumentException If parameters are missing or invalid
     * @throws RuntimeException If the task does not exist or update fails
     */
    public function execute(Request $req): array
    {
        $id = RequestValidator::getInt($req, 'id', 'Task ID must be a numeric string.');
        $userId = RequestValidator::getString($req, 'user_id', 'User ID is required.');
        $isDone = RequestValidator::getBool($req, 'is_done', 'Invalid status value.');

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
            'task' => (array) $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
