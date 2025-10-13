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
 * Class AddTaskService
 *
 * Handles the logic for adding a new task to the system.
 *
 * - Validates required input fields (`title`, `user_id`)
 * - Adds task record into the database
 * - Computes pagination data via {@see TaskPaginator}
 *
 * @package App\Api\Tasks\Service
 */
class AddTaskService
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
     * Execute the process of adding a new task.
     *
     * - Validates required parameters (`title`, `user_id`)
     * - Adds a task record to the database
     * - Returns task data and updated pagination info
     *
     * @param Request $req Incoming request containing task data
     *
     * @return array{
     *     task: array,
     *     totalPages: int
     * } Returns the added task data and total page count
     *
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws RuntimeException If the task insertion fails in the database
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

        // Return created task data and pagination info
        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
