<?php

declare(strict_types=1);

namespace App\DB;

use PDO;

/**
 * Class RefreshTokenQueries
 *
 * Handles database operations for refresh tokens.
 *
 * @package App\DB
 */
class RefreshTokenQueries
{
    private PDO $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new refresh token.
     *
     * @param string $userId
     * @param string $hash
     * @param int $expiresAt
     *
     * @return void
     */
    public function create(string $userId, string $hash, int $expiresAt): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (:uid, :hash, :exp)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':hash' => $hash,
            ':exp' => $expiresAt
        ]);
    }

    /**
     * Get all token IDs associated with a user.
     *
     * @param string $userId
     *
     * @return array<int, string> List of token IDs
     */
    public function getTokensByUserId(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM refresh_tokens WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute([':uid' => $userId]);
        /** @var array<int, string> $result */
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }

    /**
     * Delete multiple tokens by their IDs.
     *
     * @param array<int, string> $ids
     *
     * @return void
     */
    public function deleteTokens(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM refresh_tokens WHERE id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($ids));
    }

    /**
     * Remove all expired tokens for a specific user.
     *
     * @param string $userId
     * @param int $now Current timestamp
     *
     * @return void
     */
    public function cleanupExpired(string $userId, int $now): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :uid AND expires_at < :now");
        $stmt->execute([
            ':uid' => $userId,
            ':now' => $now
        ]);
    }

    /**
     * Retrieve token data by its hash.
     *
     * @param string $hash
     *
     * @return array<string, mixed>|null Token data or null if not found
     */
    public function getByHash(string $hash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT user_id, expires_at 
            FROM refresh_tokens 
            WHERE token_hash = :hash
        ");
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * Delete a specific token by its hash.
     *
     * @param string $hash
     *
     * @return void
     */
    public function deleteByHash(string $hash): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE token_hash = :hash");
        $stmt->execute([':hash' => $hash]);
    }

    /**
     * Delete all tokens belonging to a specific user.
     *
     * @param string $userId
     *
     * @return void
     */
    public function deleteAllForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    }
}
