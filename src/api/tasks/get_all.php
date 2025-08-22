<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/pagination.php';

/**
 * Handle retrieving all tasks
 *
 * @param TaskQueries $taskObj Instance of TaskQueries for database operations
 *
 * @throws RuntimeException If retrieving tasks fails or no tasks exist
 */
function handleGetTasks(TaskQueries $taskObj)
{
    // Fetch all tasks from the database
    $result = $taskObj->getAllTasks();

    // Check if the database operation was successful
    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to retrieve tasks: $errorInfo");
    }

    // Ensure there is data to return
    if (!$result->hasData()) {
        throw new RuntimeException('No tasks found.');
    }

    // Calculate total pages for pagination (assuming 10 items per page)
    $totalPages = calculateTotalPages($taskObj, 10);

    // Return JSON response with tasks and pagination info
    jsonResponse(true, 'success', 'Tasks retrieved successfully.', [
        'tasks' => $result->data
    ], $totalPages);
}
