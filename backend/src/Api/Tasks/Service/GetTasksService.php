<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class GetTasksService
 *
 * Handles the retrieval of user tasks from the database.
 *
 * - Validates the `user_id` parameter.
 * - Fetches all tasks associated with the user.
 * - Calculates total pagination pages.
 *
 * @package App\Api\Tasks\Service
 */
class GetTasksService
{
    /** @var TaskQueries Database handler for task operations */
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
     * Execute the process of retrieving tasks for a user.
     *
     * - Validates required parameter `user_id`.
     * - Retrieves all tasks belonging to the user.
     * - Calculates pagination information.
     *
     * @param Request $req Request object containing query parameters.
     *
     * @return array{
     *     task: array<int, array<string, mixed>>
     * } Array containing task data.
     *
     * @throws InvalidArgumentException If 'user_id' is missing or invalid.
     * @throws RuntimeException If tasks cannot be retrieved from the database.
     */
    public function execute(Request $req): array
    {
        // Retrieve user ID from authenticated session
        $userId = RequestValidator::getAuthUserId($req);

        // Fetch tasks via TaskQueries
        $result = $this->taskQueries->getTasksByUserID($userId);
        RequestValidator::ensureSuccess($result, 'retrieve tasks', false, true);

        /** @var array<int, array<string, mixed>> $tasks */
        $tasks = (array) $result->data;
        foreach ($tasks as &$task) {
            unset($task['user_id']);
            unset($task['created_at']);
        }
        unset($task);

        return [
            'task' => $tasks,
        ];
    }
}
