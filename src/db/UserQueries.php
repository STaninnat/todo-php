<?php
require_once __DIR__ . '/QueryResult.php';

class UserQueries
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function failFromStmt(PDOStatement $stmt): QueryResult
    {
        return QueryResult::fail($stmt->errorInfo());
    }


    public function createUser(string $id, string $username, string $email, string $pass): QueryResult
    {
        $query = "INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id, $username, $email, $pass])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getUserByID($id);
    }


    public function getUserByName(string $username): QueryResult
    {
        $query = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$username])) {
            return $this->failFromStmt($stmt);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user === false) {
            $user = null;
        }

        return QueryResult::ok($user, $user ? 1 : 0);
    }

    public function getUserByID(string $id): QueryResult
    {
        $query = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
            return $this->failFromStmt($stmt);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user === false) {
            $user = null;
        }

        return QueryResult::ok($user, $user ? 1 : 0);
    }

    public function checkUserExists(string $username, string $email): QueryResult
    {
        $query = "SELECT EXISTS(
            SELECT 1 FROM users WHERE username = ? OR email = ?
        )";

        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$username, $email])) {
            return $this->failFromStmt($stmt);
        }

        $exists = (bool)$stmt->fetchColumn();
        return QueryResult::ok($exists, $exists ? 1 : 0);
    }

    public function updateUser(string $id, string $username, string $email): QueryResult
    {
        $query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$username, $email, $id])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getUserByID($id);
    }


    public function deleteUser(string $id): QueryResult
    {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
            return $this->failFromStmt($stmt);
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }
}
