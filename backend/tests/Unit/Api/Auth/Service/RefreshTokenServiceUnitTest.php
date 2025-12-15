<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Api\Auth\Service\RefreshTokenService;
use App\DB\Database;
use App\Utils\JwtService;
use PDO;
use PDOStatement;

/**
 * Class RefreshTokenServiceUnitTest
 *
 * Unit tests for {@see RefreshTokenService}.
 *
 * Verifies logical flow of creating, verifying, and revoking tokens using DB and JWT mocks.
 *
 * @package Tests\Unit\Auth
 */
class RefreshTokenServiceUnitTest extends TestCase
{
    /** @var PDO&\PHPUnit\Framework\MockObject\MockObject Mocked Database connection */
    private $pdo;

    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject Mocked JWT service */
    private $jwt;

    /** @var RefreshTokenService Service under test */
    private RefreshTokenService $service;

    /**
     * Set up test environment with mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Mock PDO
        $this->pdo = $this->createMock(PDO::class);
        $db = $this->createMock(Database::class);
        $db->method('getConnection')->willReturn($this->pdo);

        // Mock JwtService
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new RefreshTokenService($db, $this->jwt);
    }

    /**
     * Test create() generates token, hashes it, and persists it.
     *
     * @return void
     */
    public function testCreateGeneratesAndSavesToken(): void
    {
        $userId = 'user123';
        $token = 'random_token_string';
        $hash = 'hashed_token_string';

        $this->jwt->expects($this->once())
            ->method('createRefreshToken')
            ->willReturn($token);

        $this->jwt->expects($this->once())
            ->method('hashRefreshToken')
            ->with($token)
            ->willReturn($hash);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) use ($userId, $hash) {
                return $params[':uid'] === $userId &&
                    $params[':hash'] === $hash &&
                    isset($params[':exp']);
            }));

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->service->create($userId);

        $this->assertEquals($token, $result);
    }

    /**
     * Test verify() returns user ID when token is valid and not expired.
     *
     * @return void
     */
    public function testVerifyReturnsUserIdOnSuccess(): void
    {
        $token = 'valid_token';
        $hash = 'hashed_token';
        $userId = 'user123';

        $this->jwt->expects($this->once())
            ->method('hashRefreshToken')
            ->with($token)
            ->willReturn($hash);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':hash' => $hash]);

        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['user_id' => $userId, 'expires_at' => time() + 3600]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->service->verify($token);

        $this->assertEquals($userId, $result);
    }

    /**
     * Test verify() throws exception when token is invalid or not found.
     *
     * @return void
     */
    public function testVerifyThrowsOnInvalidToken(): void
    {
        $token = 'invalid_token';
        $hash = 'hashed_invalid';

        $this->jwt->expects($this->once())
            ->method('hashRefreshToken')
            ->willReturn($hash);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false); // Not found

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->service->verify($token);
    }

    /**
     * Test verify() revokes token and throws exception when token is expired.
     *
     * @return void
     */
    public function testVerifyRevokesAndThrowsOnExpiredToken(): void
    {
        $token = 'expired_token';
        $hash = 'hashed_expired';

        $this->jwt->expects($this->exactly(2)) // Once for verify, once for revoke
            ->method('hashRefreshToken')
            ->with($token)
            ->willReturn($hash);

        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetch')
            ->willReturn(['user_id' => 'user123', 'expires_at' => time() - 3600]);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->expects($this->once())
            ->method('execute')
            ->with([':hash' => $hash]);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtSelect, $stmtDelete);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token expired');

        $this->service->verify($token);
    }
}
