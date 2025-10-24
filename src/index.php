<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Api\Request;
use App\Api\Router;
use App\Api\RouterApp;
use App\DB\Database;
use App\Utils\Logger;
use App\Utils\SystemClock;
use App\Utils\NativeFileSystem;
use App\Utils\JsonResponder;

try {
    $logger = new Logger(
        __DIR__ . '/../Logs',
        new NativeFileSystem(),
        new SystemClock(),
        true,
        30
    );
    $router = new Router();
    $database = new Database();

    $app = new RouterApp($router, $logger, $database);

    $router->addMiddleware(function (Request $request) use ($logger) {
        $logger->info("=== Debug Middleware ===");
        $logger->info("Request Method: {$request->method}");
        $logger->info("Request Path: {$request->path}");
        $logger->info("Query Params: " . json_encode($request->query));
        $logger->info("Body Params: " . json_encode($request->body));
        $logger->info("========================");
    });

    $app->dispatch();
} catch (\Throwable $e) {
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
