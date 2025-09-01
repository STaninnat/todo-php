<?php

namespace App\api\tasks;

use function App\utils\calculateTotalPages;
use function App\utils\jsonResponse;
use App\db\TaskQueries;
use InvalidArgumentException;
use RuntimeException;

/**
 * Mark a task as done or undone
 *
 * @param TaskQueries $taskObj
 * @param array $input
 *
 * @throws InvalidArgumentException
 * @throws Exception
 */
function handleMarkDoneTask(TaskQueries $taskObj, array $input): void
{
    $id = trim(strip_tags($input['id'] ?? ''));
    $userID = trim(strip_tags($input['user_id'] ?? ''));
    $is_done = isset($input['is_done']) ? (int)$input['is_done'] : 0;

    if ($id === '' || $userID === '') {
        throw new InvalidArgumentException('Task ID and User ID are required.');
    }
    if (!in_array($is_done, [0, 1], true)) {
        throw new InvalidArgumentException('Invalid status value.');
    }

    // Retrieve existing task
    $taskResult = $taskObj->getTaskByID($id, $userID);
    if (!$taskResult->success || !$taskResult->hasData()) {
        throw new RuntimeException('No task found.');
    }

    $result = $taskObj->markDone($id, (bool)$is_done, $userID);

    if (!$result->success || !$result->isChanged()) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
        throw new RuntimeException("Failed to mark task: $errorInfo");
    }

    $totalPages = calculateTotalPages($taskObj, 10);

    jsonResponse(true, 'success', 'Task status updated successfully.', [
        'task' => $result->data
    ], $totalPages);
}
