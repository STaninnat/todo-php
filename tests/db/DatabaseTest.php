<?php
require_once __DIR__ . '/../../src/db/Database.php';

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testGetConnectionReturnsMockPdo()
    {
        $mockPdo = $this->createMock(PDO::class);

        $db = new class($mockPdo) extends Database {
            public function __construct($pdo)
            {
                $this->pdo = $pdo;
            }
        };

        $this->assertInstanceOf(PDO::class, $db->getConnection());
        $this->assertSame($mockPdo, $db->getConnection());
    }
}
