<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\RefreshTokenService;
use App\DB\RefreshTokenQueries;
use App\Utils\JwtService;

/**
 * Class RefreshTokenServiceUnitTest
 *
 * Unit tests for {@see RefreshTokenService}.
 *
 * Verifies logical flow of creating, verifying, and revoking tokens using DB and JWT mocks.
 *
 * @package Tests\Unit\Utils
 */
class RefreshTokenServiceUnitTest extends TestCase
{
    /** @var RefreshTokenQueries&\PHPUnit\Framework\MockObject\MockObject Mocked Queries */
    private $queries;

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
        // Mock Queries
        $this->queries = $this->createMock(RefreshTokenQueries::class);

        // Mock JwtService
        $this->jwt = $this->createMock(JwtService::class);

        $this->service = new RefreshTokenService($this->queries, $this->jwt);
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

        $this->queries->expects($this->once())
            ->method('create')
            ->with($userId, $hash, $this->anything());

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

        $this->queries->expects($this->once())
            ->method('getByHash')
            ->with($hash)
            ->willReturn(['user_id' => $userId, 'expires_at' => time() + 3600]);

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

        $this->queries->expects($this->once())
            ->method('getByHash')
            ->with($hash)
            ->willReturn(null);

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

        $this->queries->expects($this->once())
            ->method('getByHash')
            ->with($hash)
            ->willReturn(['user_id' => 'user123', 'expires_at' => time() - 3600]);

        $this->queries->expects($this->once())
            ->method('deleteByHash')
            ->with($hash);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token expired');

        $this->service->verify($token);
    }
}
