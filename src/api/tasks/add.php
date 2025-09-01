<?php

namespace App\api\tasks;

use function App\utils\calculateTotalPages;
use function App\utils\jsonResponse;
use App\db\TaskQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handle adding a new task via POST request
 *
 * @param TaskQueries $taskObj Instance of TaskQueries for database operations
 *
 * @throws InvalidArgumentException If task title is missing
 * @throws RuntimeException If adding the task fails
 */
function handleAddTask(TaskQueries $taskObj, array $input): void
{
    // Sanitize and retrieve POST inputs
    $title       = trim(strip_tags($input['title'] ?? ''));
    $description = trim(strip_tags($input['description'] ?? ''));
    $userID = trim(strip_tags($input['user_id'] ?? ''));

    if ($title === '') {
        throw new InvalidArgumentException('Task title is required.');
    }
    if ($userID === '') {
        throw new InvalidArgumentException('User ID is required.');
    }

    // Attempt to add the task to the database
    $result = $taskObj->addTask($title, $description, $userID);

    // Check if the database operation was successful
    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to add task: $errorInfo");
    }

    // Verify that the task was actually added
    if (!$result->isChanged() || !$result->hasData()) {
        throw new RuntimeException('Task was not added.');
    }

    // Calculate total pages for pagination (assuming 10 items per page)
    $totalPages = calculateTotalPages($taskObj, 10);

    // Return JSON response with the new task and pagination info
    jsonResponse(true, 'success', 'Task added successfully', [
        'task' => $result->data
    ], $totalPages);
}
