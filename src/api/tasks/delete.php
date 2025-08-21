<?php
require_once __DIR__ . '/../../db/Database.php';
require_once __DIR__ . '/../../db/TaskQueries.php';
require_once __DIR__ . '/../../db/QueryResult.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/pagination.php';

// Initialize database and get PDO connection
$dbInstance = new Database();
$pdo = $dbInstance->getConnection();

try {
    // Create TaskQueries instance for task operations
    $taskObj = new TaskQueries($pdo);

    // Ensure the request is POST and has a task 'id'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        // Validate and sanitize task ID
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id === null) {
            throw new InvalidArgumentException('Task ID is required.');
        }
        if ($id === false) {
            throw new InvalidArgumentException('Invalid task ID format.');
        }

        // Attempt to delete the task
        $result = $taskObj->deleteTask($id);

        // Check for query errors
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to delete task: $errorInfo");
        }

        // Ensure a task was actually deleted
        if (!$result->isChanged()) {
            throw new RuntimeException('Task not found or already deleted.');
        }

        // Calculate total pages for pagination (assuming 10 tasks per page)
        $totalPages = calculateTotalPages($taskObj, 10);

        // Send JSON success response with deleted task ID and total pages
        jsonResponse(true, 'success', 'Task deleted successfully.', [
            'id' => $id
        ], $totalPages);
    } else {
        // Handle invalid request method or missing ID
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    // Send JSON error response with exception message
    jsonResponse(false, 'error', $e->getMessage());
}
