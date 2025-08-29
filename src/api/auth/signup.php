<?php
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/cookies.php';
require_once __DIR__ . '/../../utils/response.php';

use Ramsey\Uuid\Uuid;

function handleSignup(UserQueries $userObj, array $input): void
{
    $username = trim(strip_tags($input['username'] ?? ''));
    $email    = trim(strip_tags($input['email'] ?? ''));
    $password = trim(strip_tags($input['password'] ?? ''));

    if ($username === '') {
        throw new InvalidArgumentException('Username is required.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Valid email is required.');
    }
    if ($password === '') {
        throw new InvalidArgumentException('Password is required.');
    }

    $existsResult = $userObj->checkUserExists($username, $email);
    if (!$existsResult->success) {
        $errorInfo = $existsResult->error ? implode(' | ', $existsResult->error) : 'Unknown error';
        throw new RuntimeException("Failed to check user existence: $errorInfo");
    }
    if ($existsResult->data === true) {
        throw new InvalidArgumentException("Username or email already exists.");
    }

    $id = Uuid::uuid4()->toString();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $result = $userObj->createUser($id, $username, $email, $hashedPassword);
    if (!$result->success || !$result->isChanged()) {
        $errorInfo = $result->error ? implode(' | ', $result->error) : 'Unknown error';
        throw new RuntimeException("Failed to sign up: $errorInfo");
    }

    $user = $result->data;

    $token = createJwt([
        'id'       => $user['id'],
    ]);

    setAccessTokenCookie($token, time() + JWT_EXPIRE);

    jsonResponse(true, 'success', 'Signup successful.');
}
