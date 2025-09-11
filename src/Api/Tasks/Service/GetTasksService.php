<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\DB\TaskQueries;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class GetTasksService
 *
 * Service responsible for retrieving tasks for a specific user.
 *
 * It validates input data, fetches tasks from the database via TaskQueries,
 * and calculates pagination information.
 *
 * @package App\Api\Tasks\Service
 */
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
     * Validates the input data, retrieves tasks for the specified user,
     * and calculates the total pages for task pagination.
     *
     * @param array $input Input data containing 'user_id'.
     *
     * @return array Array containing task data and total pages.
     *
     * @throws InvalidArgumentException If 'user_id' is missing.
     * @throws RuntimeException If tasks could not be retrieved.
     */
    public function execute(array $input): array
    {
        // Sanitize and extract input values
        $userId = trim(strip_tags($input['user_id'] ?? ''));

        // Validate required field
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }

        // Fetch tasks via TaskQueries
        $result = $this->taskQueries->getTasksByUserID($userId);
        if (!$result->success || !$result->hasData()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'No tasks found.';
            throw new RuntimeException("Failed to retrieve tasks: $errorInfo");
        }

        // Calculate total pages for pagination
        $paginator = new TaskPaginator($this->taskQueries);
        $totalPages = $paginator->calculateTotalPages(10);

        // Return task data and total pages
        return [
            'task' => $result->data,
            'totalPages' => $totalPages,
        ];
    }
}
