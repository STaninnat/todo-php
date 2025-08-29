<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

function handleDeleteUser(UserQueries $userObj, array $input): void
{
    $userID = trim(strip_tags($input['user_id'] ?? ''));
    if ($userID === '') {
        throw new InvalidArgumentException('User ID is required.');
    }

    $result = $userObj->deleteUser($userID);

    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to delete user: $errorInfo");
    }

    clearAccessTokenCookie();

    jsonResponse(true, 'success', 'User deleted successfully.');
}
