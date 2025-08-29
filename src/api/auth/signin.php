<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

function handleSignin(UserQueries $userObj, array $input): void
{
    $username = trim(strip_tags($input['username'] ?? ''));
    $password = trim(strip_tags($input['password'] ?? ''));

    if ($username === '') {
        throw new InvalidArgumentException('Username is required.');
    }
    if ($password === '') {
        throw new InvalidArgumentException('Password is required.');
    }

    // Find user by username
    $result = $userObj->getUserByName($username);
    if (!$result->success) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to fetch user: $errorInfo");
    }
    if (!$result->hasData() || !$result->data) {
        throw new InvalidArgumentException('Invalid username or password.');
    }

    $user = $result->data;

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new InvalidArgumentException('Invalid username or password.');
    }

    // Issue JWT
    $token = createJwt([
        'id' => $user['id'],
    ]);

    // Set cookie
    setAccessTokenCookie($token, time() + JWT_EXPIRE);

    jsonResponse(true, 'success', 'Signin successful.');
}
