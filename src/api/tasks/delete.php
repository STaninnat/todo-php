<?php

namespace App\api\tasks;

use function App\utils\calculateTotalPages;
use function App\utils\jsonResponse;
use App\db\TaskQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handle deleting a task via POST request or provided data
 *
 * @param TaskQueries $taskObj Instance of TaskQueries for database operations
 * @param array|null $data Optional data array (default: $_POST)
 *
 * @throws InvalidArgumentException If task ID is missing or invalid
 * @throws RuntimeException If deleting the task fails
 */
function handleDeleteTask(TaskQueries $taskObj, array $input): void
{
    $id = trim(strip_tags($input['id'] ?? ''));
    $userID = trim(strip_tags($input['user_id'] ?? ''));

    if ($id === '') {
        throw new InvalidArgumentException('Task ID is required.');
    }
    if ($userID === '') {
        throw new InvalidArgumentException('User ID is required.');
    }

    // Attempt to delete the task from the database
    $result = $taskObj->deleteTask($id, $userID);

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
