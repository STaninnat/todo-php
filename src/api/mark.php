<?php
require_once '../db/db.php';
require_once '../db/queries.php';
require_once '../utils/formate_date.php';
require_once '../utils/pagination.php';
require_once '../utils/response.php';

try {
    $taskObj = new Task($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $is_done = filter_input(INPUT_POST, 'is_done', FILTER_VALIDATE_INT);

        if (!$id || !in_array($is_done, [0, 1], true)) {
            throw new InvalidArgumentException('Invalid input.');
        }

        $task = $taskObj->getTasksByID($id);
        if (empty($tasks)) {
            throw new Exception('No tasks found.');
        }

        $marked = $taskObj->markDone($id, (bool)$is_done);
        if ($marked) {
            $updatedTask = $taskObj->getTasksByID($id);

            $totalPages = calculateTotalPages($taskObj, 10);

            jsonResponse(true, 'success', 'Task status updated', [
                'task' => $updatedTask,
                'updated_at' => formateDateBkk($updatedTask['updated_at'])
            ], $totalPages);
        } else {
            throw new Exception('Failed to update status.');
        }
    } else {
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'error', $e->getMessage());
}
