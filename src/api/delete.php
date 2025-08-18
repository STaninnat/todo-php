<?php
require_once '../db/db.php';
require_once '../db/queries.php';
require_once '../utils/pagination.php';
require_once '../utils/response.php';

try {
    $taskObj = new Task($pdo);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === null) {
            throw new InvalidArgumentException('Task ID is required.');
        }
        if ($id === false) {
            throw new InvalidArgumentException('Invalid task ID format.');
        }

        $deleted = $taskObj->deleteTask($id);
        if (!$deleted) {
            throw new RuntimeException('Failed to delete task.');
        }

        $totalPages = calculateTotalPages($taskObj, 10);

        jsonResponse(true, 'success', 'Task deleted successfully.', [
            'id' => $id
        ], $totalPages);
    } else {
        throw new InvalidArgumentException('Invalid task ID.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'error', $e->getMessage());
}
