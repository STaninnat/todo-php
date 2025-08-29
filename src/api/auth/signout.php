<?php
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

function handleSignout(): void
{
    clearAccessTokenCookie();
    jsonResponse(true, 'success', 'Signed out successfully.');
}
