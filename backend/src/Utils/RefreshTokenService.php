<?php

declare(strict_types=1);

namespace App\Utils;

use App\DB\RefreshTokenQueries;
use InvalidArgumentException;

/**
 * Class RefreshTokenService
 *
 * Handles creation, verification, and revocation of refresh tokens.
 *
 * @package App\Utils
 */
class RefreshTokenService
{
    /** @var RefreshTokenQueries Database queries for refresh tokens */
    private RefreshTokenQueries $queries;

    /** @var JwtService Service for generating and hashing tokens */
    private JwtService $jwt;

    /**
     * @param RefreshTokenQueries $queries Database queries
     * @param JwtService          $jwt     JWT Service for hashing/generating
     */
    public function __construct(RefreshTokenQueries $queries, JwtService $jwt)
    {
        $this->queries = $queries;
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

        $this->queries->create($userId, $hash, $expiresAt);

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

        if ($keepCount < 0) {
            $keepCount = 0;
        }

        // Fetch all current tokens ordered by newest first
        $rows = $this->queries->getTokensByUserId($userId);

        // If we have fewer than we can keep, do nothing
        if (count($rows) <= $keepCount) {
            return;
        }

        // Identify tokens to remove (everything after the kept ones)
        $idsToDelete = array_slice($rows, $keepCount);

        if (!empty($idsToDelete)) {
            $this->queries->deleteTokens($idsToDelete);
        }
    }

    /**
     * Remove expired tokens for a specific user.
     *
     * @param string $userId
     */
    private function cleanupExpired(string $userId): void
    {
        $this->queries->cleanupExpired($userId, time());
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
        $row = $this->queries->getByHash($hash);

        if ($row === null) {
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
        $this->queries->deleteByHash($hash);
    }

    /**
     * Revoke all tokens for a user (optional utility).
     *
     * @param string $userId
     */
    public function revokeAllForUser(string $userId): void
    {
        $this->queries->deleteAllForUser($userId);
    }
}
