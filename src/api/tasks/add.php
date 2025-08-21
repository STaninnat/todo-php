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

    // Check if the request is a POST request and has a 'title' parameter
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        // Sanitize input
        $title = trim(strip_tags(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW)));
        $description = trim(strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW)));

        // Validate required fields
        if ($title === null || $title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }

        // Add the task using TaskQueries
        $result = $taskObj->addTask($title, $description);

        // Check for errors returned by the query
        if (!$result->success) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
            throw new RuntimeException("Failed to add task: $errorInfo");
        }

        // Ensure the task was actually added and data is available
        if (!$result->isChanged() || !$result->hasData()) {
            throw new RuntimeException('Task was not added.');
        }

        // Calculate total pages for pagination (assuming 10 tasks per page)
        $totalPages = calculateTotalPages($taskObj, 10);

        // Send JSON success response including the new task and total pages
        jsonResponse(true, 'success', 'Task added successfully', [
            'task' => $result->data
        ], $totalPages);
    } else {
        // Handle invalid request method or missing title
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    // Send JSON error response with the exception message
    jsonResponse(false, 'error', $e->getMessage());
}
