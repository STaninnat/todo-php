<?php

declare(strict_types=1);

namespace Tests\E2E;

use App\DB\Database;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PDO;

/**
 * Class TestCase
 *
 * Base class for all E2E tests.
 * Handles database connection and cleanup (truncation) between tests.
 *
 * @package Tests\E2E
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * @var PDO Database connection instance
     */
    protected PDO $pdo;

    /**
     * Set up the test environment.
     *
     * Establishes a database connection and truncates all tables
     * to ensure a clean state before each test execution.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Establish DB connection
        // We assume bootstrap.e2e.php has already run migrations
        $this->pdo = (new Database())->getConnection();

        // Clean up database BEFORE each test to ensure a clean slate
        $this->truncateTables();
    }

    /**
     * Truncate all tables to prevent data bloat and ensure test isolation.
     *
     * We disable foreign key checks to allow truncating in any order.
     * Skips the 'phinxlog' table to preserve migration history.
     *
     * @return void
     */
    private function truncateTables(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        // Get all table names
        $stmt = $this->pdo->query("SHOW TABLES");

        if ($stmt !== false) {
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                if (!is_string($table)) {
                    continue;
                }
                // Skip phinxlog table to preserve migration history
                if ($table === 'phinxlog') {
                    continue;
                }
                $this->pdo->exec("TRUNCATE TABLE `$table`");
            }
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
