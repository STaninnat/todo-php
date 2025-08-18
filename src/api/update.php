<?php
require_once '../db/db.php';
require_once '../db/queries.php';
require_once '../utils/formate_date.php';
require_once '../utils/pagination.php';
require_once '../utils/response.php';

try {
    $taskObj = new Task($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['title'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
        $is_done = filter_input(INPUT_POST, 'is_done', FILTER_VALIDATE_INT, [
            'options' => ['default' => 0]
        ]);

        $title = strip_tags($title);
        $description = strip_tags($description);

        if ($id === null) {
            throw new InvalidArgumentException('Task ID is required.');
        }
        if ($id === false) {
            throw new InvalidArgumentException('Invalid task ID format.');
        }

        $task = $taskObj->getTasksByID($id);
        if (empty($task)) {
            throw new InvalidArgumentException('No tasks found.');
        }

        if ($title === null || $title === '') {
            throw new InvalidArgumentException('Task title is required.');
        }

        $updated = $taskObj->updateTask($id, $title, $description, $is_done);
        if ($updated) {
            $updatedTask = $taskObj->getTasksByID($id);

            $totalPages = calculateTotalPages($taskObj, 10);

            jsonResponse(true, 'success', 'Task updated successfully.', [
                'task' => $updatedTask,
                'updated_at' => formateDateBkk($updatedTask['updated_at'])
            ], $totalPages);
        } else {
            throw new Exception('Failed to update task or no changes made.');
        }
    } else {
        throw new InvalidArgumentException('Invalid request.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'error', $e->getMessage());
}
