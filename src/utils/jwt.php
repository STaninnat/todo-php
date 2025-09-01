<?php

namespace App\utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;
use RuntimeException;
use Throwable;

const JWT_ALGO = 'HS256';
const JWT_EXPIRE = 3600;
const JWT_REFRESH_THRESHOLD = 600;

// Load .env once
(static function () {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();
})();

function jwt_secret(): string
{
    $secret = $_ENV['JWT_SECRET'] ?? '';
    if ($secret === '') {
        throw new RuntimeException('JWT_SECRET is not set in .env');
    }
    return $secret;
}

function createJwt(array $claims): string
{
    $now = time();
    $payload = array_merge($claims, [
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + JWT_EXPIRE,
    ]);
    return JWT::encode($payload, jwt_secret(), JWT_ALGO);
}

function decodeJwtStrict(string $token): array
{
    $decoded = JWT::decode($token, new Key(jwt_secret(), JWT_ALGO));
    return (array)$decoded;
}

function verifyJwt(?string $token): ?array
{
    if (!$token) return null;
    try {
        return decodeJwtStrict($token);
    } catch (Throwable $e) {
        return null;
    }
}

function shouldRefresh(array $payload): bool
{
    $exp = $payload['exp'] ?? 0;
    return ($exp - time()) < JWT_REFRESH_THRESHOLD;
}

function refreshFromPayload(array $payload): string
{
    unset($payload['iat'], $payload['nbf'], $payload['exp']);
    return createJwt($payload);
}
