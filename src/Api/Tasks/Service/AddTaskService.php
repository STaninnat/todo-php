<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\DB\TaskQueries;
use App\Utils\TaskPaginator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class AddTaskService
 *
 * Service responsible for adding a new task to the system.
 *
 * It validates input data, interacts with the database via TaskQueries,
 * and calculates pagination information for tasks.
 *
 * @package App\Api\Tasks\Service
 */
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
     * Validates the input data, adds a new task to the database, and
     * calculates the total pages for task pagination.
     *
     * @param array $input Input data containing 'title', 'description', and 'user_id'.
     *
     * @return array Array containing the added task data and total pages.
     *
     * @throws InvalidArgumentException If required input fields are missing.
     * @throws RuntimeException If the task could not be added to the database.
     */
    public function execute(array $input): array
    {
        // Sanitize and extract input values
        $title = trim(strip_tags($input['title'] ?? ''));
        $description = trim(strip_tags($input['description'] ?? ''));
        $userId = trim(strip_tags($input['user_id'] ?? ''));

        // Validate required fields
        if ($title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }
        if ($userId === '') {
            throw new InvalidArgumentException('User ID is required.');
        }

        // Attempt to add the task via TaskQueries
        $result = $this->taskQueries->addTask($title, $description, $userId);
        if (!$result->success || !$result->isChanged() || !$result->hasData()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to add task: $errorInfo");
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
