<?php
require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../db/TaskQueries.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/Router.php';

// include handlers
require_once __DIR__ . '/tasks/add.php';
require_once __DIR__ . '/tasks/delete.php';
require_once __DIR__ . '/tasks/get_all.php';
require_once __DIR__ . '/tasks/update.php';

$db = new Database();
$pdo = $db->getConnection();
$taskObj = new TaskQueries($pdo);

$router = new Router();

$router->register('POST', 'add', fn() => handleAddTask($taskObj));
$router->register('POST', 'update', fn() => handleUpdateTask($taskObj));
$router->register('POST', 'delete', fn() => handleDeleteTask($taskObj));
$router->register('GET', 'get', fn() => handleGetTasks($taskObj));
$router->register('PUT', 'update', function () use ($taskObj) {
    parse_str(file_get_contents('php://input'), $_PUT);
    handleUpdateTask($taskObj, $_PUT);
});
$router->register('DELETE', 'delete', function () use ($taskObj) {
    parse_str(file_get_contents('php://input'), $_DELETE);
    handleDeleteTask($taskObj, $_DELETE);
});

$router->dispatch();
