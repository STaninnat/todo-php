<?php
require_once '../db/db.php';
require_once '../db/queries.php';

function calculateTotalPages(Task $taskObj, int $perPage = 10): int
{
    try {
        $totalTasks = $taskObj->getTotalTasks();
        return (int) ceil($totalTasks / $perPage);
    } catch (Exception $e) {
        return 1;
    }
}
