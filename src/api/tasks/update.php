<?php

namespace App\api\tasks;

use function App\utils\calculateTotalPages;
use function App\utils\jsonResponse;
use App\db\TaskQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Update task title/description/status
 *
 * @param TaskQueries $taskObj
 * @param array $input
 *
 * @throws InvalidArgumentException
 * @throws Exception
 */
function handleUpdateTask(TaskQueries $taskObj, array $input): void
{
    $id = trim(strip_tags($input['id'] ?? ''));
    $userID = trim(strip_tags($input['user_id'] ?? ''));
    $title = trim(strip_tags($input['title'] ?? ''));
    $description = trim(strip_tags($input['description'] ?? ''));
    $is_done = isset($input['is_done']) ? (int)$input['is_done'] : 0;

    if ($id === '' || $userID === '') {
        throw new InvalidArgumentException('Task ID and User ID are required.');
    }
    if ($title === '') {
        throw new InvalidArgumentException('Task title is required.');
    }
    if (!in_array($is_done, [0, 1], true)) {
        $is_done = 0;
    }

    // Retrieve existing task
    $taskResult = $taskObj->getTaskByID($id, $userID);
    if (!$taskResult->success || !$taskResult->hasData()) {
        throw new RuntimeException('No task found.');
    }

    $result = $taskObj->updateTask($id, $title, $description, (bool)$is_done, $userID);

    if (!$result->success || !$result->isChanged()) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
        throw new RuntimeException("Failed to update task: $errorInfo");
    }

    $totalPages = calculateTotalPages($taskObj, 10);

    jsonResponse(true, 'success', 'Task updated successfully.', [
        'task' => $result->data
    ], $totalPages);
}
