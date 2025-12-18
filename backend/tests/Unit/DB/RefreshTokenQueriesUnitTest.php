<?php

declare(strict_types=1);

namespace Tests\Unit\DB;

use PHPUnit\Framework\TestCase;
use App\DB\RefreshTokenQueries;
use PDO;
use PDOStatement;

/**
 * Class RefreshTokenQueriesUnitTest
 *
 * Unit tests for RefreshTokenQueries using mocked PDO.
 *
 * @package Tests\Unit\DB
 */
class RefreshTokenQueriesUnitTest extends TestCase
{
    /** @var PDO&\PHPUnit\Framework\MockObject\MockObject */
    private $pdo;

    private RefreshTokenQueries $queries;

    /**
     * Setup mocks before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->queries = new RefreshTokenQueries($this->pdo);
    }

    /**
     * Test: create should execute INSERT statement with correct parameters.
     *
     * @return void
     */
    public function testCreateExecutesInsert(): void
    {
        $userId = 'u1';
        $hash = 'h1';
        $exp = 12345;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([
                ':uid' => $userId,
                ':hash' => $hash,
                ':exp' => $exp
            ]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO refresh_tokens'))
            ->willReturn($stmt);

        $this->queries->create($userId, $hash, $exp);
    }

    /**
     * Test: getTokensByUserId should execute SELECT and return array of IDs.
     *
     * @return void
     */
    public function testGetTokensByUserIdReturnsArray(): void
    {
        $userId = 'u1';
        $expected = ['id1', 'id2'];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':uid' => $userId]);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn($expected);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT id FROM refresh_tokens'))
            ->willReturn($stmt);

        $result = $this->queries->getTokensByUserId($userId);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test: deleteTokens should execute DELETE with correct placeholders.
     *
     * @return void
     */
    public function testDeleteTokensExecutesDeleteWithPlaceholders(): void
    {
        $ids = ['id1', 'id2'];

        $stmt = $this->createMock(PDOStatement::class);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM refresh_tokens WHERE id IN (?,?)'))
            ->willReturn($stmt);

        $stmt->expects($this->once())
            ->method('execute')
            ->with($ids);

        $this->queries->deleteTokens($ids);
    }

    /**
     * Test: cleanupExpired should execute DELETE for expired tokens.
     *
     * @return void
     */
    public function testCleanupExpiredExecutesDelete(): void
    {
        $userId = 'u1';
        $now = 1000;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':uid' => $userId, ':now' => $now]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM refresh_tokens WHERE user_id = :uid AND expires_at < :now'))
            ->willReturn($stmt);

        $this->queries->cleanupExpired($userId, $now);
    }

    /**
     * Test: getByHash should execute SELECT and return token data.
     *
     * @return void
     */
    public function testGetByHashReturnsArrayOrNull(): void
    {
        $hash = 'h1';
        $row = ['user_id' => 'u1', 'expires_at' => 123];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':hash' => $hash]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($row);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT user_id, expires_at'))
            ->willReturn($stmt);

        $result = $this->queries->getByHash($hash);
        $this->assertEquals($row, $result);
    }

    /**
     * Test: deleteByHash should execute DELETE statement.
     *
     * @return void
     */
    public function testDeleteByHashExecutesDelete(): void
    {
        $hash = 'h1';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':hash' => $hash]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM refresh_tokens WHERE token_hash = :hash'))
            ->willReturn($stmt);

        $this->queries->deleteByHash($hash);
    }

    /**
     * Test: deleteAllForUser should execute DELETE for all user's tokens.
     *
     * @return void
     */
    public function testDeleteAllForUserExecutesDelete(): void
    {
        $userId = 'u1';

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':uid' => $userId]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM refresh_tokens WHERE user_id = :uid'))
            ->willReturn($stmt);

        $this->queries->deleteAllForUser($userId);
    }
}
