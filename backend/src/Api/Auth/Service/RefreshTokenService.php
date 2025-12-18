<?php

declare(strict_types=1);

namespace App\Api\Auth\Service;

use App\DB\Database;
use App\Utils\JwtService;
use InvalidArgumentException;
use PDO;

/**
 * Class RefreshTokenService
 *
 * Handles creation, verification, and revocation of refresh tokens.
 *
 * @package App\Api\Auth\Service
 */
class RefreshTokenService
{
    /** @var PDO Database connection for executing queries */
    private PDO $pdo;

    /** @var JwtService Service for generating and hashing tokens */
    private JwtService $jwt;

    /**
     * @param Database   $db  Database connection
     * @param JwtService $jwt JWT Service for hashing/generating
     */
    public function __construct(Database $db, JwtService $jwt)
    {
        $this->pdo = $db->getConnection();
        $this->jwt = $jwt;
    }

    /**
     * Create a new refresh token for a user.
     *
     * @param string $userId
     * @param int $ttlSeconds
     * @return string The plain text refresh token
     */
    public function create(string $userId, int $ttlSeconds = 604800): string
    {
        // 1. Hygiene: Remove expired tokens first
        $this->cleanupExpired($userId);

        // 2. Enforce Max Sessions Policy (Limit to 2 active sessions)
        $this->enforceSessionLimit($userId, 2);

        $token = $this->jwt->createRefreshToken();
        $hash = $this->jwt->hashRefreshToken($token);
        $expiresAt = time() + $ttlSeconds;

        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (:uid, :hash, :exp)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':hash' => $hash,
            ':exp' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Enforce a maximum number of active sessions.
     * Keeps the newest (limit - 1) tokens to make room for the new one.
     *
     * @param string $userId
     * @param int $limit Total max sessions allowed (including the new one)
     */
    private function enforceSessionLimit(string $userId, int $limit): void
    {
        // We need to keep ($limit - 1) existing tokens so that adding 1 results in $limit.
        $keepCount = $limit - 1;

        if ($keepCount < 0)
            $keepCount = 0;

        // Fetch all current tokens ordered by newest first
        $stmt = $this->pdo->prepare("SELECT id FROM refresh_tokens WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // If we have fewer than we can keep, do nothing
        if (count($rows) <= $keepCount) {
            return;
        }

        // Identify tokens to remove (everything after the kept ones)
        $idsToDelete = array_slice($rows, $keepCount);

        if (!empty($idsToDelete)) {
            // Create placeholders for IN clause (?,?,?)
            $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
            $sql = "DELETE FROM refresh_tokens WHERE id IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($idsToDelete);
        }
    }

    /**
     * Remove expired tokens for a specific user.
     *
     * @param string $userId
     */
    private function cleanupExpired(string $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :uid AND expires_at < :now");
        $stmt->execute([
            ':uid' => $userId,
            ':now' => time()
        ]);
    }

    /**
     * Verify a refresh token and return the user ID.
     *
     * @param string $token
     * @return string The user ID
     * @throws InvalidArgumentException If token is invalid or expired
     */
    public function verify(string $token): string
    {
        $hash = $this->jwt->hashRefreshToken($token);

        $stmt = $this->pdo->prepare("
            SELECT user_id, expires_at 
            FROM refresh_tokens 
            WHERE token_hash = :hash
        ");
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new InvalidArgumentException("Invalid refresh token.");
        }

        if ($row['expires_at'] < time()) {
            // Cleanup expired
            $this->revoke($token);
            throw new InvalidArgumentException("Refresh token expired.");
        }

        if (!isset($row['user_id']) || !is_scalar($row['user_id'])) {
            throw new InvalidArgumentException("Invalid refresh token structure.");
        }

        return (string) $row['user_id'];
    }

    /**
     * Revoke a refresh token.
     *
     * @param string $token
     */
    public function revoke(string $token): void
    {
        $hash = $this->jwt->hashRefreshToken($token);
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE token_hash = :hash");
        $stmt->execute([':hash' => $hash]);
    }

    /**
     * Revoke all tokens for a user (optional utility).
     *
     * @param string $userId
     */
    public function revokeAllForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    }
}
