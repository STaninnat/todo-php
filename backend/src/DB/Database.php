<?php

declare(strict_types=1);

namespace App\DB;

use PDO;
use PDOException;
use Exception;

/**
 * Class Database
 * 
 * Manages the PDO connection to the database.
 * Uses DatabaseConfig to retrieve connection details.
 * 
 * @package App\DB
 */
class Database
{
    /**
     * @var PDO The PDO connection instance
     */
    protected PDO $pdo;

    /**
     * Database constructor.
     * 
     * Establishes a PDO connection using the provided configuration or defaults.
     * 
     * @param string|null         $dsn    Optional DSN string. If null, constructed from config.
     * @param string|null         $user   Optional DB username. If null, retrieved from config.
     * @param string|null         $pass   Optional DB password. If null, retrieved from config.
     * @param DatabaseConfig|null $config Optional DatabaseConfig instance for dependency injection.
     * 
     * @throws DatabaseConfigException If required configuration variables are missing.
     * @throws Exception               If the database connection fails.
     */
    public function __construct(
        ?string $dsn = null,
        ?string $user = null,
        ?string $pass = null,
        ?DatabaseConfig $config = null
    ) {
        // Use provided config or instantiate a new one (Hybrid approach)
        $config = $config ?? new DatabaseConfig();

        $host = $config->get('DB_HOST');
        $db = $config->get('DB_NAME');
        $user = $user ?? $config->get('DB_USER');
        $pass = $pass ?? $config->get('DB_PASS');
        $port = $config->get('DB_PORT') ?? '3306';

        // Validate required configuration variables
        if (!$host) {
            throw new DatabaseConfigException('Missing DB_HOST');
        }
        if (!$db) {
            throw new DatabaseConfigException('Missing DB_NAME');
        }
        if (!$user) {
            throw new DatabaseConfigException('Missing DB_USER');
        }

        if (is_string($pass)) {
            $pass = trim($pass);
            if ($pass === '') {
                throw new DatabaseConfigException('DB_PASS must not be empty');
            }
        }

        // Construct DSN if not provided
        $dsn ??= "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        try {
            // Create PDO connection with error handling and default fetch mode
            $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('DB connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the active PDO connection.
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
