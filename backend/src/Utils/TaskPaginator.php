<?php

declare(strict_types=1);

namespace App\Utils;

use App\DB\TaskQueries;

/**
 * Class TaskPaginator
 *
 * Service class to handle pagination logic for tasks.
 * Depends on TaskQueries to fetch total task count.
 * 
 * @package App\Utils
 */
class TaskPaginator
{
    private TaskQueries $taskQueries;

    /**
     * Constructor
     *
     * @param TaskQueries $taskQueries Injected dependency for task queries
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Calculate total number of pages for tasks based on items per page.
     *
     * - Fetches total task count via TaskQueries
     * - Returns at least 1 page if no tasks found or on error
     * - Uses ceil to round up partial pages
     *
     * @param int $perPage Number of tasks per page (default: 10)
     * @return int Total number of pages (minimum 1)
     */
    public function calculateTotalPages(int $perPage = 10): int
    {
        $result = $this->taskQueries->getTotalTasks();

        // Return 1 if query fails or data is invalid
        if (!$result->success || !is_int($result->data) || $result->data < 1) {
            return 1;
        }

        // Calculate total pages using ceiling division
        return (int) ceil($result->data / $perPage);
    }
}
