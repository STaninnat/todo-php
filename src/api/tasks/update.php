<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/pagination.php';
require_once __DIR__ . '/../../utils/formate_date.php';

/**
 * Handle updating a task based on provided action
 *
 * @param TaskQueries $taskObj Instance of TaskQueries for database operations
 * @param array|null $data Optional data array (default: $_POST)
 *
 * @throws InvalidArgumentException If required fields are missing or invalid
 * @throws Exception If database operations fail
 */
function handleUpdateTask(TaskQueries $taskObj, ?array $data = null)
{
    // Use provided data or fallback to $_POST
    $input = $data ?? $_POST;

    // Extract action and task ID from input
    $action = $input['action'] ?? null;
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === null) {
        throw new InvalidArgumentException('Task ID is required.');
    }
    if ($id === false) {
        throw new InvalidArgumentException('Invalid task ID format.');
    }

    // Retrieve existing task
    $taskResult = $taskObj->getTaskByID($id);
    if (!$taskResult->success || !$taskResult->hasData()) {
        throw new Exception('No task found.');
    }

    $result = null;

    // Perform action based on inpu
    switch ($action) {
        case 'mark_done':
            // Validate status value
            $is_done = filter_var($input['is_done'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($is_done, [0, 1], true)) {
                throw new InvalidArgumentException('Invalid status value.');
            }

            // Mark the task as done or undone
            $result = $taskObj->markDone($id, (bool)$is_done);
            break;

        case 'update':
            // Sanitize and retrieve input fields
            $title = trim(strip_tags(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW)));
            $description = trim(strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW)));
            $is_done = filter_input(INPUT_POST, 'is_done', FILTER_VALIDATE_INT);

            if ($title === '') {
                throw new InvalidArgumentException('Task title is required.');
            }

            // Ensure status is either 0 or 1
            if (!in_array($is_done, [0, 1], true)) {
                $is_done = 0;
            }

            // Update the task in the database
            $result = $taskObj->updateTask($id, $title, $description, (bool)$is_done);
            break;

        default:
            throw new InvalidArgumentException('Unknown action.');
    }

    // Check if the update succeeded
    if (!$result->success || !$result->isChanged()) {
        $errorMsg = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
        throw new Exception($errorMsg);
    }

    // Fetch the updated task
    $updatedTaskResult = $taskObj->getTaskByID($id);
    if (!$updatedTaskResult->success || !$updatedTaskResult->hasData()) {
        throw new Exception('Failed to fetch updated task.');
    }

    $updatedTask = $updatedTaskResult->data;

    // Calculate total pages for pagination (assuming 10 items per page)
    $totalPages = calculateTotalPages($taskObj, 10);

    // Return JSON response with updated task and formatted update timestamp
    jsonResponse(true, 'success', 'Task updated successfully.', [
        'task' => $updatedTask,
        'updated_at' => formateDateBkk($updatedTask['updated_at'])
    ], $totalPages);
}
