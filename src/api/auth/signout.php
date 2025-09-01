<?php

namespace App\api\auth;

use function App\utils\clearAccessTokenCookie;
use function App\utils\jsonResponse;

function handleSignout(): void
{
    clearAccessTokenCookie();
    jsonResponse(true, 'success', 'Signed out successfully.');
}
