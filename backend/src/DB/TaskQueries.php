<?php

declare(strict_types=1);

namespace App\DB;

use PDO;
use PDOStatement;

/**
 * Class TaskQueries
 * 
 * TaskQueries provides CRUD operations for the "tasks" table.
 * 
 * @package App\DB
 */
class TaskQueries
{
    // PDO instance for database operations
    private PDO $pdo;

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
     * 
     * @return QueryResult
     */
    private function failFromStmt(PDOStatement|false $stmt): QueryResult
    {
        $errorInfo = $stmt instanceof PDOStatement
            ? $stmt->errorInfo()
            : $this->pdo->errorInfo();

        $errorStrings = [];
        foreach ($errorInfo as $v) {
            $errorStrings[] = is_scalar($v) || $v === null ? (string) $v : gettype($v);
        }

        return QueryResult::fail($errorStrings);
    }

    /**
     * Add a new task to the database
     *
     * @param string $title
     * @param string $description
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function addTask(string $title, string $description, string $userId): QueryResult
    {
        $query = "INSERT INTO tasks (title, description, user_id) VALUES (?, ?, ?)";

        try {
            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return $this->failFromStmt(false);
            }

            $ok = $stmt->execute([$title, $description, $userId]);
            if ($ok === false) {
                return $this->failFromStmt($stmt);
            }

            $id = (int) $this->pdo->lastInsertId();
            return $this->getTaskByID($id, $userId);
        } catch (\PDOException $e) {
            return QueryResult::fail([$e->getMessage()]);
        }
    }

    /**
     * Get all tasks, ordered by completion status and last updated
     *
     * @return QueryResult
     */
    public function getAllTasks(): QueryResult
    {
        $query = "SELECT * FROM tasks ORDER BY is_done ASC, updated_at DESC";

        $stmt = $this->pdo->prepare($query);
        if ($stmt === false) {
            return $this->failFromStmt(false);
        }

        if (!$stmt->execute()) {
            return $this->failFromStmt($stmt);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($data, $stmt->rowCount());
    }

    /**
     * Get a single task by its ID
     *
     * @param int $id
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function getTaskByID(int $id, string $userId): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE id = ? AND user_id = ? LIMIT 1";

        $stmt = $this->pdo->prepare($query);
        if ($stmt === false) {
            return $this->failFromStmt(false);
        }

        if (!$stmt->execute([$id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        $task = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return QueryResult::ok($task, $task ? 1 : 0);
    }

    /**
     * Get tasks for a specific page (pagination)
     *
     * @param int $page
     * @param int $perPage
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function getTasksByPage(int $page, int $perPage = 10, ?string $userId = null): QueryResult
    {
        try {
            $offset = ($page - 1) * $perPage;

            $query = "SELECT * FROM tasks";
            if ($userId !== null) {
                $query .= " WHERE user_id = :user_id";
            }

            $query .= " ORDER BY is_done ASC, updated_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return $this->failFromStmt(false);
            }

            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return $this->failFromStmt($stmt);
            }

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return QueryResult::ok($data, count($data));
        } catch (\Throwable $e) {
            return QueryResult::fail([$e->getMessage()]);
        }
    }

    /**
     * Get All tasks By user id
     *
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function getTasksByUserID(string $userId): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE user_id = ? ORDER BY is_done ASC, updated_at DESC";

        try {
            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return $this->failFromStmt(false);
            }

            $ok = $stmt->execute([$userId]);
            if ($ok === false) {
                return $this->failFromStmt($stmt);
            }

            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return QueryResult::ok($tasks, count($tasks));
        } catch (\Throwable $e) {
            return QueryResult::fail([$e->getMessage()]);
        }
    }

    /**
     * Mark a task as done or not done
     *
     * @param int $id
     * @param bool $isDone
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function markDone(int $id, bool $isDone, string $userId): QueryResult
    {
        $query = "UPDATE tasks SET is_done = ? WHERE id = ? AND user_id = ?";

        try {
            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return $this->failFromStmt(false);
            }

            if (!$stmt->execute([$isDone ? 1 : 0, $id, $userId])) {
                return $this->failFromStmt($stmt);
            }

            // Return updated task
            return $this->getTaskByID($id, $userId);
        } catch (\PDOException $e) {
            // Gracefully handle SQL or connection errors
            return QueryResult::fail([$e->getMessage()]);
        }
    }

    /**
     * Update task details
     *
     * @param int $id
     * @param string $title
     * @param string $description
     * @param bool $isDone
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function updateTask(int $id, string $title, string $description, bool $isDone, string $userId): QueryResult
    {
        $query = "UPDATE tasks SET title = ?, description = ?, is_done = ? WHERE id = ? AND user_id = ?";

        try {
            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return $this->failFromStmt(false);
            }

            if (!$stmt->execute([$title, $description, $isDone ? 1 : 0, $id, $userId])) {
                return $this->failFromStmt($stmt);
            }

            return $this->getTaskByID($id, $userId);
        } catch (\PDOException $e) {
            return QueryResult::fail([$e->getMessage()]);
        }
    }

    /**
     * Delete a task by its ID
     *
     * @param int $id
     * @param string $userId
     * 
     * @return QueryResult
     */
    public function deleteTask(int $id, string $userId): QueryResult
    {
        $query = "DELETE FROM tasks WHERE id = ? AND user_id = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt === false) {
            return $this->failFromStmt(false);
        }

        if (!$stmt->execute([$id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }

    /**
     * Count total tasks for a user
     *
     * @param string $userId
     * 
     * @return int
     */
    public function countTasksByUserId(string $userId): int
    {
        $query = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ?";

        $stmt = $this->pdo->prepare($query);
        if ($stmt === false || !$stmt->execute([$userId])) {
            return 0;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['total'] : 0;
    }
}