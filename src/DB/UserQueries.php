<?php

namespace App\DB;

use PDO;
use PDOStatement;


/**
 * UserQueries provides CRUD operations for the "users" table.
 */
class UserQueries
{
    // PDO instance for database operations
    private $pdo;

    /**
     * Constructor sets the PDO connection
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Helper method to create a failed QueryResult from a PDOStatement
     *
     * @param PDOStatement $stmt
     * @return QueryResult
     */
    private function failFromStmt(PDOStatement $stmt): QueryResult
    {
        return QueryResult::fail($stmt->errorInfo());
    }

    /**
     * Create a new user in the database
     *
     * @param string $id
     * @param string $username
     * @param string $email
     * @param string $pass
     * @return QueryResult
     */
    public function createUser(string $id, string $username, string $email, string $pass): QueryResult
    {
        $query = "INSERT INTO users (id, username, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id, $username, $email, $pass])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getUserByID($id);
    }

    /**
     * Get a single user by username
     *
     * @param string $username
     * @return QueryResult
     */
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

    /**
     * Get a single user by ID
     *
     * @param string $id
     * @return QueryResult
     */
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

    /**
     * Check if a user exists by username or email
     *
     * @param string $username
     * @param string $email
     * @return QueryResult
     */
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

    /**
     * Update user details (username and email)
     *
     * @param string $id
     * @param string $username
     * @param string $email
     * @return QueryResult
     */
    public function updateUser(string $id, string $username, string $email): QueryResult
    {
        $query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$username, $email, $id])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getUserByID($id);
    }

    /**
     * Delete a user by ID
     *
     * @param string $id
     * @return QueryResult
     */
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
