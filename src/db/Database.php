<?php
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

class Database
{
    protected $pdo;

    public function __construct($dsn = null, $user = null, $pass = null)
    {
        if ($dsn === null) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->safeLoad();

            $host = $_ENV['DB_HOST'] ?? null;
            $db   = $_ENV['DB_NAME'] ?? null;
            $user = $_ENV['DB_USER'] ?? null;
            $pass = $_ENV['DB_PASS'] ?? null;

            if (!$host || !$db || !$user) {
                throw new Exception("DB environment variables are not set.");
            }

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        }

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("DB connection failed: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}
