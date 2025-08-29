<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

function handleUpdateUser(UserQueries $userObj, array $input): void
{
    $userID = trim(strip_tags($input['user_id'] ?? ''));
    if ($userID === '') {
        throw new InvalidArgumentException('User ID is required.');
    }

    $username = trim(strip_tags($input['username'] ?? ''));
    $email    = trim(strip_tags($input['email'] ?? ''));

    if ($username === '') {
        throw new InvalidArgumentException('Username is required.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Valid email is required.');
    }

    $existsResult = $userObj->checkUserExists($username, $email);
    if (!$existsResult->success) {
        $errorInfo = $existsResult->error ? implode(' | ', $existsResult->error) : 'Unknown error';
        throw new RuntimeException("Failed to check user existence: $errorInfo");
    }
    if ($existsResult->data === true) {
        throw new RuntimeException("Username or email already exists.");
    }

    $result = $userObj->updateUser($userID, $username, $email);

    if (!$result->success || !$result->isChanged()) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to update user: $errorInfo");
    }

    $userResp = [
        'username' => $result->data['username'],
        'email'    => $result->data['email'],
    ];

    jsonResponse(true, 'success', 'User updated successfully.', $userResp);
}
