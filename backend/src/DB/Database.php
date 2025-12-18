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
        $dbSource = getenv('DB_SOURCE') ?: $_ENV['DB_SOURCE'] ?? null;

        // Define variable mapping based on source
        $prefix = '';
        if ($dbSource === 'local') {
            $prefix = 'LC_';
        } elseif ($dbSource === 'aiven' || $dbSource === 'cloud') {
            $prefix = 'AIVEN_';
        }

        // Helper to get var with fallback to standard
        $getVar = function (string $name) use ($prefix): ?string {
            $prefixedName = $prefix . $name;
            // Try prefixed env var first
            $val = getenv($prefixedName) ?: $_ENV[$prefixedName] ?? null;
            if (is_string($val) && $val !== '') {
                return $val;
            }
            // Fallback to standard
            $val = getenv($name) ?: $_ENV[$name] ?? null;
            return is_string($val) ? $val : null;
        };

        $host = $getVar('DB_HOST');
        $db = $getVar('DB_NAME');
        $user = $user ?? $getVar('DB_USER');
        $pass = $pass ?? $getVar('DB_PASS');
        $port = $getVar('DB_PORT') ?? '3306';

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
        if (is_string($pass)) {
            $pass = trim($pass);
            if ($pass === '') {
                throw new Exception("DB_PASS must not be empty.");
            }
        }
        if ($port === '') {
            $port = '3306';
        }

        $dsn = $dsn ?? "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        // Attempt to create PDO connection
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // Handle SSL if configured
            $sslCa = $getVar('DB_SSL_CA');
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
