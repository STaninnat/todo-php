<?php

/**
 * db.php
 * 
 * This file handles the database connection setup using PDO.
 * It loads configuration from environment variables (.env file)
 * and establishes a connection to the MySQL database.
 * 
 * If the connection fails, it stops script execution and outputs an error message.
 */

require __DIR__ . "/../../vendor/autoload.php";

use Dotenv\Dotenv;

/**
 * Load environment variables from .env file located in the project root.
 * 
 * Using vlucas/phpdotenv package to securely manage sensitive credentials.
 */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

/**
 * Retrieve database configuration variables from environment.
 * These should be defined in the .env file:
 * - DB_HOST: Database host address
 * - DB_NAME: Database name
 * - DB_USER: Username for database access
 * - DB_PASS: Password for database access
 */
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

/**
 * Create Data Source Name (DSN) string for PDO connection.
 * This includes host, database name, and charset to ensure UTF-8 encoding.
 */
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    /**
     * Create a new PDO instance.
     * 
     * Options used:
     * - ERRMODE_EXCEPTION: Throws exceptions on errors for easier debugging.
     * - DEFAULT_FETCH_MODE: Sets default fetch mode to associative arrays.
     */
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // echo "DB connection successful";
} catch (PDOException $e) {
    /**
     * Handle any connection errors.
     * Script execution stops here with a descriptive error message.
     */
    die("DB connection failed: " . $e->getMessage());
}
