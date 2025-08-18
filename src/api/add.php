<?php
require_once '../db/db.php';
require_once '../db/queries.php';
require_once '../utils/pagination.php';
require_once '../utils/response.php';

try {
    $taskObj = new Task($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));

        $title = strip_tags($title);
        $description = strip_tags($description);

        if ($title === null || $title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }

        $newTaskID = $taskObj->addTasks($title, $description);
        if ($newTaskID !== null) {
            throw new RuntimeException('Failed to add task.');
        }

        $totalPages = calculateTotalPages($taskObj, 10);

        jsonResponse(true, 'success', 'Task added successfully', [
            'id' => $newTaskID,
            'title' => $title,
            'description' => $description
        ], $totalPages);
    } else {
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'error', $e->getMessage());
}
