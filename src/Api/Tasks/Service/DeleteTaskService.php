<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

class DeleteTaskService
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
     * Execute the task deletion process.
     *
     * @param Request $req Request object containing task data.
     *
     * @return array Array containing deleted task ID and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing or invalid.
     * @throws RuntimeException If the task could not be deleted.
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

        return [
            'id' => $id,
            'totalPages' => $totalPages,
        ];
    }
}
