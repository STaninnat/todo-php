<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\DB\TaskQueries;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class MarkDoneTaskService
 *
 * Service responsible for updating the completion status of a task.
 *
 * It validates input data, verifies task existence, updates the task status
 * in the database via TaskQueries, and calculates pagination information.
 *
 * @package App\Api\Tasks\Service
 */
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
     * Validates the input data, checks if the task exists, updates the
     * task completion status, and calculates the total pages for task pagination.
     *
     * @param array $input Input data containing 'id', 'user_id', and 'is_done'.
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
        $isDone = isset($input['is_done']) ? (int)$input['is_done'] : 0;

        // Validate required fields
        if ($id === '' || $userId === '') {
            throw new InvalidArgumentException('Task ID and User ID are required.');
        }
        if (!in_array($isDone, [0, 1], true)) {
            throw new InvalidArgumentException('Invalid status value.');
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

        // Update task completion status
        $result = $this->taskQueries->markDone($idInt, (bool)$isDone, $userId);
        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
            throw new RuntimeException("Failed to mark task: $errorInfo");
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
