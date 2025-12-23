<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Api\Router;
use App\Api\RouterApp;
use App\DB\Database;
use App\Utils\Logger;
use App\Utils\SystemClock;
use App\Utils\NativeFileSystem;
use App\Utils\JsonResponder;
use App\Api\Middlewares\DebugMiddleware;
use App\Utils\EnvLoader;


// Load environment variables if running locally (not in Docker/Render)
if (!getenv('APP_ENV')) {
    EnvLoader::load(dirname(__DIR__));
}

$debug = filter_var(getenv('APP_DEBUG') ?? true, FILTER_VALIDATE_BOOL);

try {
    $logger = new Logger(
        new NativeFileSystem(),
        new SystemClock(),
        $debug
    );
    $router = new Router();
    $database = new Database();

    $app = new RouterApp($router, $logger, $database);

    $router->addMiddleware(new DebugMiddleware($logger));

    $app->dispatch();
} catch (Throwable $e) {
    if (isset($logger)) {
        /** @var Logger $logger */
        $logger->error("Bootstrap error: " . $e->getMessage());
    }

    JsonResponder::error(
        'Internal server error (bootstrap)',
        'error',
        500
    )->send(true, false);
}
