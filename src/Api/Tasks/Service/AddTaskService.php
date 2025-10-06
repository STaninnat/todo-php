<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

class AddTaskService
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
     * Execute the task addition process.
     *
     * @param Request $req Incoming request containing task data.
     *
     * @return array Array containing the added task data and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing.
     * @throws RuntimeException If the task could not be added to the database.
     */
    public function execute(Request $req): array
    {
        $title = RequestValidator::getStringParam($req, 'title', 'Task title is required.');
        $description = trim(strip_tags($req->body['description'] ?? '')); // optional
        $userId = RequestValidator::getStringParam($req, 'user_id', 'User ID is required.');

        // Add task to database
        $result = $this->taskQueries->addTask($title, $description, $userId);
        RequestValidator::ensureSuccess($result, 'add task');

        // Calculate total pages
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
