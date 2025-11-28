<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
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
     *     task: array<int|string, mixed>
     * } Returns the added task data
     *
     * @throws InvalidArgumentException If required fields are missing or invalid
     * @throws RuntimeException If the task insertion fails in the database
     */
    public function execute(Request $req): array
    {
        $title = RequestValidator::getString($req, 'title', 'Task title is required.');
        $description = ''; // optional
        if (isset($req->body['description']) && is_string($req->body['description'])) {
            $description = trim(strip_tags($req->body['description']));
        }

        // Retrieve user ID from authenticated session
        $userId = RequestValidator::getAuthUserId($req);

        // Add task to database
        $result = $this->taskQueries->addTask($title, $description, $userId);
        RequestValidator::ensureSuccess($result, 'add task');

        // Return created task data
        $taskData = (array) $result->data;
        unset($taskData['user_id']);
        unset($taskData['created_at']);

        return [
            'task' => $taskData,
        ];
    }
}
