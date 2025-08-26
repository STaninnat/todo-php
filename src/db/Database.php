<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

/**
 * Database class for managing PDO connection
 */
class Database
{
    // PDO instance
    protected $pdo;

    /**
     * Constructor initializes a database connection
     *
     * @param string|null $dsn  Optional DSN string
     * @param string|null $user Optional DB username
     * @param string|null $pass Optional DB password
     *
     * @throws Exception If required environment variables are missing
     */
    public function __construct($dsn = null, $user = null, $pass = null)
    {
        // If DSN is not provided, load environment variables
        if ($dsn === null) {
            $envFile = '.env'; // default
            if (getenv('APP_ENV') === 'testing') {
                $envFile = '.env.test';
            }

            $dotenv = Dotenv::createImmutable(dirname(__DIR__), $envFile);
            $dotenv->safeLoad();

            $host = $_ENV['DB_HOST'] ?? null;
            $db   = $_ENV['DB_NAME'] ?? null;
            $user = $_ENV['DB_USER'] ?? null;
            $pass = $_ENV['DB_PASS'] ?? null;

            // Ensure required environment variables are set
            if (!$host || !$db || !$user) {
                throw new Exception("DB environment variables are not set.");
            }

            // Build DSN string for MySQL connection
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        }

        // Attempt to create PDO connection
        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // Stop execution if connection fails
            die("DB connection failed: " . $e->getMessage());
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
