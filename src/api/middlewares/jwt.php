<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';

function refreshJwtMiddleware(Request $req): void
{
    $token = getAccessTokenFromCookie();
    $payload = verifyJwt($token);

    if ($payload) {
        $req->auth = $payload;
        if (shouldRefresh($payload)) {
            $newToken = refreshFromPayload($payload);
            setAccessTokenCookie($newToken, time() + JWT_EXPIRE);
        }
    }
}

function requireAuthMiddleware(Request $req): void
{
    if (!$req->auth) {
        throw new RuntimeException('Unauthorized. You must be logged in.');
    }
}
