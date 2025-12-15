<?php

declare(strict_types=1);

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

/**
 * Class JwtService
 *
 * Service class for handling JWT creation, verification, and refresh.
 * 
 * @package App\Utils
 */
class JwtService
{
    private string $secret;
    private string $algo;
    private int $expire;
    private int $refreshThreshold;

    /**
     * Constructor
     *
     * @param string|null $secret           Secret key for signing JWT. Falls back to env JWT_SECRET if null.
     * @param string      $algo             Algorithm to use for JWT (default: HS256)
     * @param int         $expire           Token expiration in seconds (default: 3600)
     * @param int         $refreshThreshold Time before expiration to trigger refresh (default: 600)
     *
     * @throws RuntimeException if secret key is not set
     */
    public function __construct(
        ?string $secret = null,
        string $algo = 'HS256',
        int $expire = 3600,
        int $refreshThreshold = 600
    ) {
        // Use provided secret or fallback to environment variable
        /** @var string $envSecret */
        $envSecret = $_ENV['JWT_SECRET'] ?? '';
        $this->secret = $secret ?? $envSecret;
        if ($this->secret === '') {
            throw new RuntimeException('JWT_SECRET is not set');
        }

        $this->algo = $algo;
        $this->expire = $expire;
        $this->refreshThreshold = $refreshThreshold;
    }

    /**
     * Create a new JWT with provided claims
     *
     * @param array<string, mixed>    $claims Custom payload claims
     * @param int|null                $now    Current timestamp, optional (for testing)
     *
     * @return string Encoded JWT token
     */
    public function create(array $claims, ?int $now = null): string
    {
        $now = $now ?? time();

        // Merge custom claims with standard JWT claims
        $payload = array_merge($claims, [
            'iat' => $now,                  // Issued at time
            'nbf' => $now,                  // Not valid before
            'exp' => $now + $this->expire,  // Expiration time
        ]);

        // Encode payload using secret and algorithm
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Decode a JWT strictly
     *
     * @param string $token JWT token
     *
     * @return array<string, mixed> Decoded payload
     */
    public function decodeStrict(string $token): array
    {
        // Decode JWT and cast stdClass to array
        /** @var array<string, mixed> $decodedArr */
        $decodedArr = (array) JWT::decode($token, new Key($this->secret, $this->algo));

        return $decodedArr;
    }

    /**
     * Verify a JWT and return payload or null if invalid
     *
     * @param string|null $token JWT token
     *
     * @return array<string, mixed>|null Decoded payload or null if token invalid
     */
    public function verify(?string $token): ?array
    {
        if (!$token)
            return null;

        try {
            // Attempt to decode token
            return $this->decodeStrict($token);
        } catch (Throwable) {
            // Catch any exception (expired, invalid, malformed)
            return null; // Return null for invalid token
        }
    }

    /**
     * Determine if token should be refreshed based on threshold
     *
     * @param array<string, mixed> $payload Decoded JWT payload
     * @param int|null             $now     Current timestamp, optional
     *
     * @return bool True if token is close to expiration
     */
    public function shouldRefresh(array $payload, ?int $now = null): bool
    {
        $exp = 0;

        // Get expiration from payload
        if (isset($payload['exp'])) {
            $val = $payload['exp'];

            if (is_int($val)) {
                $exp = $val;
            } elseif (is_numeric($val)) {
                $exp = (int) $val;
            } else {
                $exp = 0;
            }
        }

        $now = $now ?? time();  // Use provided time or current time

        // Return true if token is within refresh threshold
        return ($exp - $now) < $this->refreshThreshold;
    }

    /**
     * Refresh a JWT by creating a new token with the same claims
     *
     * @param array<string, mixed>    $payload Decoded payload to refresh
     * @param int|null                $now     Current timestamp, optional
     *
     * @return string New JWT token
     */
    public function refresh(array $payload, ?int $now = null): string
    {
        // Remove standard claims before recreating token
        unset($payload['iat'], $payload['nbf'], $payload['exp']);
        $now = $now ?? time();

        // Re-create JWT with same custom claims
        return $this->create($payload, $now);
    }

    /**
     * Create a secure random refresh token
     *
     * @return string The generated refresh token
     * @throws \Exception If random_bytes fails
     */
    public function createRefreshToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 characters
    }

    /**
     * Hash a refresh token for storage
     *
     * @param string $token The refresh token to hash
     * @return string The hashed token (SHA256)
     */
    public function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
