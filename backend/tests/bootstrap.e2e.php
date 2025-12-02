<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Integration/bootstrap_db.php';

// Load environment variables
$envFile = __DIR__ . '/../../.env.test';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2) + [1 => ''];
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

$getEnv = function (string $key, string $default = ''): string {
    $val = $_ENV[$key] ?? getenv($key);
    return is_string($val) ? $val : $default;
};

$dbHost = $getEnv('DB_HOST', 'db_test');
$dbPort = (int) ($getEnv('DB_PORT', '3306'));

// Wait for database
waitForDatabase($dbHost, $dbPort);

// Connect to database to clean up
try {
    // Connect to database to clean up
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $username = $getEnv('DB_USER', 'root');
    $password = $getEnv('DB_PASS', 'root');

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Select database
    $dbName = $getEnv('DB_NAME', 'todo_test');
    $pdo->exec("USE `$dbName`");

    echo "Dropping all tables to ensure clean state...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $stmt = $pdo->query("SHOW TABLES");
    if ($stmt !== false) {
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            if (!is_string($table)) {
                continue;
            }
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "All tables dropped.\n";

} catch (PDOException $e) {
    fwrite(STDERR, "Database cleanup failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Run Phinx migrations
echo "Running Phinx migrations for E2E tests...\n";
$phinxCmd = __DIR__ . '/../vendor/bin/phinx migrate -c ' . __DIR__ . '/../phinx.php -e development';

// Ensure we use the correct environment for Phinx
// We might need to pass environment variables to the command or ensure phinx.php reads them.
// Assuming phinx.php reads from $_ENV or similar, which we just populated.

passthru($phinxCmd, $returnVar);

if ($returnVar !== 0) {
    fwrite(STDERR, "Failed to run migrations. E2E tests aborted.\n");
    exit(1);
}

echo "Migrations completed successfully.\n";
