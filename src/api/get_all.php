<?php
require_once '../db/db.php';
require_once '../db/queries.php';
require_once '../utils/response.php';

try {
    $taskObj = new Task($pdo);

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
