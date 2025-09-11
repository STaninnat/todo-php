<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\DB\TaskQueries;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class UpdateTaskService
 *
 * Service responsible for updating an existing task's information.
 *
 * It validates input data, verifies task existence, updates task details
 * in the database via TaskQueries, and calculates pagination information.
 *
 * @package App\Api\Tasks\Service
 */
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
     * Validates the input data, checks if the task exists, updates the
     * task information (title, description, completion status), and calculates
     * the total pages for task pagination.
     *
     * @param array $input Input data containing 'id', 'user_id', 'title', 'description', and 'is_done'.
     *
     * @return array Array containing updated task data and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing or invalid.
     * @throws RuntimeException If the task could not be found or updated.
     */
    public function execute(array $input): array
    {
        // Sanitize and extract input values
        $id = trim(strip_tags($input['id'] ?? ''));
        $userId = trim(strip_tags($input['user_id'] ?? ''));
        $title = trim(strip_tags($input['title'] ?? ''));
        $description = trim(strip_tags($input['description'] ?? ''));
        $isDone = isset($input['is_done']) ? (int)$input['is_done'] : 0;

        // Validate required fields
        if ($id === '' || $userId === '') {
            throw new InvalidArgumentException('Task ID and User ID are required.');
        }
        if ($title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }
        if (!in_array($isDone, [0, 1], true)) {
            $isDone = 0;
        }
        if (!ctype_digit($id)) {
            throw new InvalidArgumentException('Task ID must be a number.');
        }

        // Convert ID to integer
        $idInt = (int) $id;

        // Verify that the task exists
        $taskResult = $this->taskQueries->getTaskByID($idInt, $userId);
        if (!$taskResult->success || !$taskResult->hasData()) {
            throw new RuntimeException('No task found.');
        }

        // Update task information
        $result = $this->taskQueries->updateTask($idInt, $title, $description, (bool)$isDone, $userId);
        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
            throw new RuntimeException("Failed to update task: $errorInfo");
        }

        // Calculate total pages for pagination
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        // Return updated task data and total pages
        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
