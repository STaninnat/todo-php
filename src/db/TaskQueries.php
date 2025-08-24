<?php
require_once __DIR__ . '/QueryResult.php';

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
     * @return QueryResult
     */
    public function addTask(string $title, string $description): QueryResult
    {
        $query = "INSERT INTO tasks (title, description) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$title, $description])) {
            return $this->failFromStmt($stmt);
        }

        $id = (int)$this->pdo->lastInsertId();
        return $this->getTaskByID($id);
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
     * @return QueryResult
     */
    public function getTaskByID(int $id): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
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
     * @return QueryResult
     */
    public function getTasksByPage(int $page, int $perPage = 10): QueryResult
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT * 
                FROM tasks 
                ORDER BY is_done ASC, updated_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return $this->failFromStmt($stmt);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($data, $stmt->rowCount());
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

    /**
     * Mark a task as done or not done
     *
     * @param int $id
     * @param bool $isDone
     * @return QueryResult
     */
    public function markDone(int $id, bool $isDone): QueryResult
    {
        $query = "UPDATE tasks SET is_done = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$isDone ? 1 : 0, $id])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getTaskByID($id);
    }

    /**
     * Update task details
     *
     * @param int $id
     * @param string $title
     * @param string $description
     * @param bool $isDone
     * @return QueryResult
     */
    public function updateTask(int $id, string $title, string $description, bool $isDone): QueryResult
    {
        $query = "UPDATE tasks 
                SET title = ?, description = ?, is_done = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$title, $description, $isDone ? 1 : 0, $id])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getTaskByID($id);
    }

    /**
     * Delete a task by its ID
     *
     * @param int $id
     * @return QueryResult
     */
    public function deleteTask(int $id): QueryResult
    {
        $query = "DELETE FROM tasks WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
            return $this->failFromStmt($stmt);
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }
}
