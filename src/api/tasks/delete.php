<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/pagination.php';

/**
 * Handle deleting a task via POST request or provided data
 *
 * @param TaskQueries $taskObj Instance of TaskQueries for database operations
 * @param array|null $data Optional data array (default: $_POST)
 *
 * @throws InvalidArgumentException If task ID is missing or invalid
 * @throws RuntimeException If deleting the task fails
 */
function handleDeleteTask(TaskQueries $taskObj, ?array $data = null)
{
    // Use provided data or fallback to $_POST
    $input = $data ?? $_POST;

    // Use provided data or fallback to $_POST

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === null) {
        throw new InvalidArgumentException('Task ID is required.');
    }
    if ($id === false) {
        throw new InvalidArgumentException('Invalid task ID format.');
    }

    // Attempt to delete the task from the database
    $result = $taskObj->deleteTask($id);

    // Check if the database operation was successful
    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to delete task: $errorInfo");
    }

    // Verify that a task was actually deleted
    if (!$result->isChanged()) {
        throw new RuntimeException('Task not found or already deleted.');
    }

    // Calculate total pages for pagination (assuming 10 items per page)
    $totalPages = calculateTotalPages($taskObj, 10);

    // Return JSON response with deleted task ID and updated pagination info
    jsonResponse(true, 'success', 'Task deleted successfully.', [
        'id' => $id
    ], $totalPages);
}
