<?php
require_once __DIR__ . '/../../db/Database.php';
require_once __DIR__ . '/../../db/TaskQueries.php';
require_once __DIR__ . '/../../db/QueryResult.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/pagination.php';
require_once __DIR__ . '/../../utils/formate_date.php';

// Initialize database and get PDO connection
$dbInstance = new Database();
$pdo = $dbInstance->getConnection();

try {
    // Create TaskQueries instance for task operations
    $taskObj = new TaskQueries($pdo);

    // Validate request method and required action parameter
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        throw new InvalidArgumentException('Invalid request.');
    }

    $action = $_POST['action'];
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Validate task ID
    if ($id === null) {
        throw new InvalidArgumentException('Task ID is required.');
    }
    if ($id === false) {
        throw new InvalidArgumentException('Invalid task ID format.');
    }

    // Fetch task by ID to ensure it exists
    $taskResult = $taskObj->getTaskByID($id);
    if (!$taskResult->success || !$taskResult->hasData()) {
        throw new Exception('No task found.');
    }

    $result = null;

    // Handle action types: mark_done or update
    switch ($action) {
        case 'mark_done':
            $is_done = filter_input(INPUT_POST, 'is_done', FILTER_VALIDATE_INT);

            // Validate status value (0 or 1)
            if (!in_array($is_done, [0, 1], true)) {
                throw new InvalidArgumentException('Invalid status value.');
            }

            // Mark the task as done or not done
            $result = $taskObj->markDone($id, (bool)$is_done);
            break;

        case 'update':
            // Sanitize input for title and description
            $title = trim(strip_tags(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW)));
            $description = trim(strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW)));
            $is_done = filter_input(INPUT_POST, 'is_done', FILTER_VALIDATE_INT);

            // Validate required title
            if ($title === null || $title === '') {
                throw new InvalidArgumentException('Task title is required.');
            }

            // Default status to 0 if invalid
            if (!in_array($is_done, [0, 1], true)) {
                $is_done = 0;
            }

            // Update the task
            $result = $taskObj->updateTask($id, $title, $description, (bool)$is_done);
            break;

        default:
            throw new InvalidArgumentException('Unknown action.');
    }

    // Check if operation succeeded and made changes
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

    // Calculate total pages for pagination (assuming 10 tasks per page)
    $totalPages = calculateTotalPages($taskObj, 10);

    // Send JSON success response with updated task data and formatted updated_at
    jsonResponse(true, 'success', 'Task updated successfully.', [
        'task' => $updatedTask,
        'updated_at' => formateDateBkk($updatedTask['updated_at'])
    ], $totalPages);
} catch (Exception $e) {
    // Send JSON error response with exception message
    jsonResponse(false, 'error', $e->getMessage());
}
