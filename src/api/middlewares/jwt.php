<?php

namespace App\api\middlewares;

use App\api\Request;
use function App\utils\getAccessTokenFromCookie;
use function App\utils\setAccessTokenCookie;
use function App\utils\verifyJwt;
use function App\utils\shouldRefresh;
use function App\utils\refreshFromPayload;
use const App\utils\JWT_EXPIRE;
use RuntimeException;

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
