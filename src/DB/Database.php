<?php

declare(strict_types=1);

namespace App\DB;

use Dotenv\Dotenv;
use PDO;
use PDOException;
use Exception;

/**
 * Class Database
 * 
 * Database class for managing PDO connection.
 * 
 * @package App\DB
 */
class Database
{
    // PDO instance
    protected PDO $pdo;

    /**
     * Constructor initializes a database connection
     *
     * @param string|null $dsn  Optional DSN string
     * @param string|null $user Optional DB username
     * @param string|null $pass Optional DB password
     *
     * @throws Exception If required environment variables are missing
     */
    public function __construct(?string $dsn = null, ?string $user = null, ?string $pass = null)
    {
        // If DSN is not provided, load environment variables
        if ($dsn === null) {
            $envFile = getenv('APP_ENV') === 'testing' ? '.env.test' : '.env';
            $dotenv = Dotenv::createImmutable(dirname(__DIR__), $envFile);
            $dotenv->safeLoad();

            $host = $_ENV['DB_HOST'] ?? null;
            $db   = $_ENV['DB_NAME'] ?? null;
            $user = $_ENV['DB_USER'] ?? null;
            $pass = $_ENV['DB_PASS'] ?? null;


            // Ensure required environment variables are set
            if (!is_string($host) || !is_string($db) || !is_string($user)) {
                throw new Exception("DB environment variables are not set.");
            }

            if ($pass !== null && !is_string($pass)) {
                throw new Exception("DB_PASS must be a string or null.");
            }

            // Build DSN string for MySQL connection
            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        }

        // Attempt to create PDO connection
        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new Exception("DB connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the PDO connection instance
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}
