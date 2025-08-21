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
    // Create TaskQueries instance for performing task operations
    $taskObj = new TaskQueries($pdo);

    // Ensure the request method is GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Retrieve all tasks
        $result = $taskObj->getAllTasks();

        // Check for query errors
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to retrieve tasks: $errorInfo");
        }

        // Ensure there is data to return
        if (!$result->hasData()) {
            throw new RuntimeException('No tasks found.');
        }

        // Calculate total pages for pagination (assuming 10 tasks per page)
        $totalPages = calculateTotalPages($taskObj, 10);

        // Send JSON success response with tasks data and total pages
        jsonResponse(true, 'success', 'Tasks retrieved successfully.', [
            'tasks' => $result->data
        ], $totalPages);
    } else {
        // Handle invalid request method
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    // Send JSON error response with exception message
    jsonResponse(false, 'error', $e->getMessage());
}
