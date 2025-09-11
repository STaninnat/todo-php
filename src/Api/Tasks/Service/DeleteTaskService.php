<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\DB\TaskQueries;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class DeleteTaskService
 *
 * Service responsible for deleting a task from the system.
 *
 * It validates input data, interacts with the database via TaskQueries,
 * and calculates pagination information for tasks after deletion.
 *
 * @package App\Api\Tasks\Service
 */
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
     * Validates the input data, deletes the specified task from the database,
     * and calculates the total pages for task pagination.
     *
     * @param array $input Input data containing 'id' and 'user_id'.
     *
     * @return array Array containing deleted task ID and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing or invalid.
     * @throws RuntimeException If the task could not be deleted.
     */
    public function execute(array $input): array
    {
        // Sanitize and extract input values
        $id = trim(strip_tags($input['id'] ?? ''));
        $userId = trim(strip_tags($input['user_id'] ?? ''));

        // Validate required fields
        if ($id === '') {
            throw new InvalidArgumentException('Task ID is required.');
        }
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }
        if (!ctype_digit($id)) {
            throw new InvalidArgumentException('Task ID must be a number.');
        }

        // Convert ID to integer
        $idInt = (int) $id;

        // Attempt to delete the task via TaskQueries
        $result = $this->taskQueries->deleteTask($idInt, $userId);
        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Task not found or already deleted.';
            throw new RuntimeException("Failed to delete task: $errorInfo");
        }

        // Calculate total pages for pagination
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        // Return deleted task ID and total pages
        return [
            'id' => $idInt,
            'totalPages' => $totalPages,
        ];
    }
}
