<?php

function getAccessTokenFromCookie(): ?string
{
    return $_COOKIE['access_token'] ?? null;
}

function setAccessTokenCookie(string $token, int $expires): void
{
    setcookie('access_token', $token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function clearAccessTokenCookie(): void
{
    setcookie('access_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function getAccessTokenCookie(): ?string
{
    return $_COOKIE['access_token'] ?? null;
}
