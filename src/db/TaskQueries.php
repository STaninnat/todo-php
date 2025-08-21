<?php
require_once __DIR__ . '/QueryResult.php';

class TaskQueries
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

    public function getAllTasks(): QueryResult
    {
        $query = "SELECT * FROM tasks ORDER BY is_done ASC, updated_at DESC";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute()) {
            return $this->failFromStmt($stmt);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return !empty($data) ? QueryResult::ok($data, $stmt->rowCount()) : QueryResult::empty();
    }

    public function getTaskByID(int $id): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
            return $this->failFromStmt($stmt);
        }

        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ? QueryResult::ok($task, 1) : QueryResult::empty();
    }

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
        return !empty($data) ? QueryResult::ok($data, $stmt->rowCount()) : QueryResult::empty();
    }

    public function getTotalTasks(): QueryResult
    {
        $query = "SELECT COUNT(*) FROM tasks";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute()) {
            return $this->failFromStmt($stmt);
        }

        $count = (int) $stmt->fetchColumn();
        return $count > 0 ? QueryResult::ok($count, $count) : QueryResult::empty();
    }

    public function markDone(int $id, bool $isDone): QueryResult
    {
        $query = "UPDATE tasks SET is_done = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$isDone ? 1 : 0, $id])) {
            return $this->failFromStmt($stmt);
        }

        return $this->getTaskByID($id);
    }

    public function updateTask(int $id, string $title, string $description, bool $isDone): QueryResult
    {
        $query = "UPDATE tasks 
                SET title = ?, description = ?, is_done = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$title, $description, $isDone ? 1 : 0, $id])) {
            return $this->failFromStmt($stmt);
        }

        if ($stmt->rowCount() === 0) {
            return QueryResult::empty();
        }

        return $this->getTaskByID($id);
    }

    public function deleteTask(int $id): QueryResult
    {
        $query = "DELETE FROM tasks WHERE id = ?";
        $stmt = $this->pdo->prepare($query);

        if (!$stmt->execute([$id])) {
            return $this->failFromStmt($stmt);
        }

        return $stmt->rowCount() > 0 ? QueryResult::ok(null, $stmt->rowCount()) : QueryResult::empty();
    }
}
