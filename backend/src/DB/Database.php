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
        // Load .env only if running outside Docker (no DB_HOST env set)
        if (!getenv('DB_HOST') && file_exists(dirname(__DIR__) . '/.env')) {
            $envFile = getenv('APP_ENV') === 'testing' ? '.env.test' : '.env';
            $dotenv = Dotenv::createImmutable(dirname(__DIR__), $envFile);
            $dotenv->safeLoad();
        }

        // Get environment variables
        $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
        $db = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
        $user = $user ?? getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
        $pass = $pass ?? getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? null;
        $port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? '3306';

        // Ensure required vars exist and are strings
        if (!is_string($host) || $host === '') {
            throw new Exception("Missing or invalid DB_HOST.");
        }
        if (!is_string($db) || $db === '') {
            throw new Exception("Missing or invalid DB_NAME.");
        }
        if (!is_string($user) || $user === '') {
            throw new Exception("Missing or invalid DB_USER.");
        }
        if ($pass !== null && !is_string($pass)) {
            throw new Exception("DB_PASS must be a string or null.");
        }
        if (!is_string($port) || $port === '') {
            $port = '3306';
        }

        $dsn = $dsn ?? "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        // Attempt to create PDO connection
        // Attempt to create PDO connection
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // Handle SSL if configured
            $sslCa = getenv('DB_SSL_CA') ?: $_ENV['DB_SSL_CA'] ?? null;
            if (is_string($sslCa) && $sslCa !== '') {
                if (!file_exists($sslCa)) {
                    throw new Exception("SSL CA file not found at: $sslCa");
                }
                $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Aiven self-signed often needs this false or strict verification
            }

            $this->pdo = new PDO($dsn, $user, $pass, $options);
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
