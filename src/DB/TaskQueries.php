<?php

namespace App\DB;

use PDO;
use PDOStatement;

/**
 * TaskQueries provides CRUD operations for the "tasks" table.
 */
class TaskQueries
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
     * Add a new task to the database
     *
     * @param string $title
     * @param string $description
     * @param string $userID
     * @return QueryResult
     */
    public function addTask(string $title, string $description, string $userId): QueryResult
    {
        $query = "INSERT INTO tasks (title, description, user_id) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$title, $description, $userId])) {
            return $this->failFromStmt($stmt);
        }

        $id = (int)$this->pdo->lastInsertId();
        return $this->getTaskByID($id, $userId);
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
     * @param string $userID
     * @return QueryResult
     */
    public function getTaskByID(int $id, string $userId): QueryResult
    {
        $query = "SELECT * FROM tasks 
            WHERE id = ? 
            AND user_id = ? 
            LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task === false) {
            $task = null;
        }

        return QueryResult::ok($task, $task ? 1 : 0);
    }

    /**
     * Get tasks for a specific page (pagination)
     *
     * @param int $page
     * @param int $perPage
     * @param string $userID
     * @return QueryResult
     */
    public function getTasksByPage(int $page, int $perPage = 10, ?string $userId = null): QueryResult
    {
        $offset = ($page - 1) * $perPage;
        $params = [];

        $query = "SELECT * FROM tasks";
        if ($userId !== null) {
            $query .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $query .= " ORDER BY is_done ASC, updated_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($userId !== null) {
            $stmt->bindValue(1, $userId); // bind userId if present
        }

        if (!$stmt->execute($params)) {
            return $this->failFromStmt($stmt);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($data, count($data));
    }

    /**
     * Get All tasks By user id
     *
     * @param string $userId
     * @return QueryResult
     */
    public function getTasksByUserID(string $userId): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE user_id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$userId])) {
            return $this->failFromStmt($stmt);
        }

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($tasks, count($tasks));
    }

    /**
     * Mark a task as done or not done
     *
     * @param int $id
     * @param bool $isDone
     * @param string $userID
     * @return QueryResult
     */
    public function markDone(int $id, bool $isDone, string $userId): QueryResult
    {
        $query = "UPDATE tasks SET is_done = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$isDone ? 1 : 0, $id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getTaskByID($id, $userId);
    }

    /**
     * Update task details
     *
     * @param int $id
     * @param string $title
     * @param string $description
     * @param bool $isDone
     * @param string $userID
     * @return QueryResult
     */
    public function updateTask(int $id, string $title, string $description, bool $isDone, string $userId): QueryResult
    {
        $query = "UPDATE tasks 
                SET title = ?, description = ?, is_done = ?
                WHERE id = ? AND user_id =?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$title, $description, $isDone ? 1 : 0, $id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getTaskByID($id, $userId);
    }

    /**
     * Delete a task by its ID
     *
     * @param int $id
     * @param string $userID
     * @return QueryResult
     */
    public function deleteTask(int $id, string $userId): QueryResult
    {
        $query = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id, $userId])) {
            return $this->failFromStmt($stmt);
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }

    /**
     * Get total number of tasks
     *
     * @return QueryResult
     */
    public function getTotalTasks(): QueryResult
    {
        $query = "SELECT COUNT(*) FROM tasks";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute()) {
            return $this->failFromStmt($stmt);
        }

        $count = (int) $stmt->fetchColumn();
        return QueryResult::ok($count, $count);
    }
}
