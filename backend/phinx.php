<?php

/**
 * Phinx Configuration File.
 *
 * This file configures the Phinx migration tool.
 * It loads environment variables from the .env file (if present)
 * and defines the database connection settings for migrations.
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Retrieve database credentials from environment variables
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? null;
$name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? null;
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? null;
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? null;
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '3306';

// Validate required configuration
if (!$host || !$name || !$user) {
    throw new RuntimeException("Missing required database configuration in .env file (DB_HOST, DB_NAME, DB_USER)");
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => $host,
            'name' => $name,
            'user' => $user,
            'pass' => $pass,
            'port' => $port,
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation'
];
