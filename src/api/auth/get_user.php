<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

function handleGetUser(UserQueries $userObj, array $input): void
{
    $userID = trim(strip_tags($input['user_id'] ?? ''));
    if ($userID === '') {
        throw new InvalidArgumentException('User ID is required.');
    }

    $result = $userObj->getUserById($userID);
    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to fetch user: $errorInfo");
    }

    if (!$result->data) {
        throw new RuntimeException("User not found.");
    }

    $userResp = [
        'username' => $result->data['username'],
        'email'    => $result->data['email'],
    ];

    jsonResponse(true, 'success', 'Got user successful.', $userResp);
}
