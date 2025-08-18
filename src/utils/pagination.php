<?php
require_once __DIR__ . '/../db/TaskQueries.php';

function calculateTotalPages(TaskQueries $taskObj, int $perPage = 10): int
{
    try {
        $totalTasks = $taskObj->getTotalTasks();
        return (int) ceil($totalTasks / $perPage);
    } catch (Exception $e) {
        return 1;
    }
}
