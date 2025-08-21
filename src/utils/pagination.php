<?php
require_once __DIR__ . '/../db/TaskQueries.php';

/**
 * Calculate total number of pages for tasks, based on items per page.
 *
 * @param TaskQueries $taskObj Instance of TaskQueries
 * @param int $perPage Number of tasks per page (default: 10)
 * @return int Total number of pages (minimum 1)
 */
function calculateTotalPages(TaskQueries $taskObj, int $perPage = 10): int
{
    // Get total number of tasks
    $result = $taskObj->getTotalTasks();

    // If query failed or data is invalid, default to 1 page
    if (!$result->success || !is_int($result->data)) {
        return 1;
    }

    // Calculate total pages and round up
    return (int) ceil($result->data / $perPage);
}
