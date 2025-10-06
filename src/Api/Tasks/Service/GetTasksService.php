<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

class GetTasksService
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
     * Execute the task retrieval process.
     *
     * @param Request $req Request object containing query parameters.
     *
     * @return array Array containing task data and total pages.
     *
     * @throws InvalidArgumentException If 'user_id' is missing.
     * @throws RuntimeException If tasks could not be retrieved.
     */
    public function execute(Request $req): array
    {
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');

        // Fetch tasks via TaskQueries
        $result = $this->taskQueries->getTasksByUserID($userId);
        RequestValidator::ensureSuccess($result, 'retrieve tasks');

        // Calculate total pages for pagination
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
