<?php
require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../db/TaskQueries.php';
require_once __DIR__ . '/../utils/response.php';

$dbInstance = new Database();
$pdo = $dbInstance->getConnection();

try {
    $taskObj = new TaskQueries($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tasks = $taskObj->getAllTasks();
        if (empty($tasks)) {
            throw new InvalidArgumentException('No tasks found.');
        }

        $totalPages = calculateTotalPages($taskObj, 10);

        jsonResponse(true, 'success', 'Tasks retrieved successfully.', [
            'tasks' => $tasks
        ], $totalPages);
    } else {
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'error', $e->getMessage());
}
