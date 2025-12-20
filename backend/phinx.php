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
$dbSource = $_ENV['DB_SOURCE'] ?? getenv('DB_SOURCE') ?? null;
$prefix = '';
if ($dbSource === 'local' || $dbSource === 'dev') {
    $prefix = 'LC_';
} elseif ($dbSource === 'aiven' || $dbSource === 'cloud') {
    $prefix = 'AIVEN_';
}

$getVar = function ($name) use ($prefix) {
    $val = $_ENV[$prefix . $name] ?? getenv($prefix . $name) ?? null;
    if ($val !== null && $val !== '') {
        return $val;
    }
    return $_ENV[$name] ?? getenv($name) ?? null;
};

$host = $getVar('DB_HOST');
$name = $getVar('DB_NAME');
$user = $getVar('DB_USER');
$pass = $getVar('DB_PASS');
$port = $getVar('DB_PORT') ?? '3306';
$sslCa = $getVar('DB_SSL_CA');

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
            // Add SSL options if configured
            'ssl_ca' => $sslCa ?? null,
            'ssl_verify' => false, // Often needed for Aiven self-signed logic in PHP naming verification
        ],
    ],
    'version_order' => 'creation'
];
