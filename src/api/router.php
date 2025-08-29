<?php
require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../db/TaskQueries.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/middlewares/jwt.php';
require_once __DIR__ . '/Router.php';

// include handlers
require_once __DIR__ . '/tasks/add.php';
require_once __DIR__ . '/tasks/delete.php';
require_once __DIR__ . '/tasks/get_all.php';
require_once __DIR__ . '/tasks/update.php';
require_once __DIR__ . '/tasks/mark.php';

require_once __DIR__ . '/users/signup.php';
require_once __DIR__ . '/users/signin.php';
require_once __DIR__ . '/users/signout.php';
require_once __DIR__ . '/users/get_user.php';
require_once __DIR__ . '/users/update_user.php';
require_once __DIR__ . '/users/delete_user.php';

$db = new Database();
$pdo = $db->getConnection();
$taskObj = new TaskQueries($pdo);
$userObj = new UserQueries($pdo);

$router = new Router();

// Global middleware: refresh JWT if needed
$router->addMiddleware('refreshJwtMiddleware');

// Auth middleware for routes that require login
$authMiddleware = ['requireAuthMiddleware'];

// --- User routes ---
$router->register('POST', '/users/signup', fn(Request $req) => handleSignup($userObj, $req->body));
$router->register('POST', '/users/signin', fn(Request $req) => handleSignin($userObj, $req->body));
$router->register('POST', '/users/signout', fn(Request $req) => handleSignout($userObj));

$router->register('PUT', '/users/update', fn(Request $req) => handleUpdateUser($userObj, $req->body), $authMiddleware);
$router->register('DELETE', '/users/delete', fn(Request $req) => handleDeleteUser($userObj, $req->body), $authMiddleware);
$router->register('GET', '/users/me', fn(Request $req) => handleGetUser($userObj, $req->query), $authMiddleware);

// --- Task routes ---
$router->register('POST', '/tasks/add', fn(Request $req) => handleAddTask($taskObj, $req->body), $authMiddleware);
$router->register('PUT', '/tasks/mark_done', fn(Request $req) => handleMarkDoneTask($taskObj, $req->body), $authMiddleware);
$router->register('PUT', '/tasks/update', fn(Request $req) => handleUpdateTask($taskObj, $req->body), $authMiddleware);
$router->register('DELETE', '/tasks/delete', fn(Request $req) => handleDeleteTask($taskObj, $req->body), $authMiddleware);
$router->register('GET', '/tasks', fn(Request $req) => handleGetTasks($taskObj, $req->body), $authMiddleware);

// Dispatch request
$router->dispatch();
