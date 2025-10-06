<?php

namespace App\api;

use App\db\Database;
use App\db\TaskQueries;
use App\db\UserQueries;
use function App\api\auth\handleSignup;
use function App\api\auth\handleSignin;
use function App\api\auth\handleSignout;
use function App\api\auth\handleUpdateUser;
use function App\api\auth\handleDeleteUser;
use function App\api\auth\handleGetUser;
use function App\api\tasks\handleAddTask;
use function App\api\tasks\handleMarkDoneTask;
use function App\api\tasks\handleUpdateTask;
use function App\api\tasks\handleDeleteTask;
use function App\api\tasks\handleGetTasks;

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
