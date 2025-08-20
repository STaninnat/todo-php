<?php
require_once __DIR__ . '/QueryResult.php';

class TaskQueries
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addTask(string $title, string $description): QueryResult
    {
        $query = "INSERT INTO tasks (title, description) VALUES (?, ?)";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$title, $description])) {
            return QueryResult::fail();
        }
        return QueryResult::ok((int)$this->pdo->lastInsertId(), 1);
    }

    public function getAllTasks(): QueryResult
    {
        $query = "SELECT * FROM tasks ORDER BY is_done ASC, updated_at DESC";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute()) {
            return QueryResult::fail();
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($data, $stmt->rowCount());
    }

    public function getTaskByID(int $id): QueryResult
    {
        $query = "SELECT * FROM tasks WHERE id = ? LIMIT 1";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$id])) {
            return QueryResult::fail();
        }

        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ? QueryResult::ok($task, 1) : QueryResult::ok(null, 0);
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
            return QueryResult::fail();
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return QueryResult::ok($data, $stmt->rowCount());
    }

    public function getTotalTasks(): QueryResult
    {
        $query = "SELECT COUNT(*) FROM tasks";

        $stmt = $this->pdo->prepare($query);
        $count = (int) $stmt->fetchColumn();

        return QueryResult::ok($count, $count);
    }

    public function markDone(int $id, bool $isDone): QueryResult
    {
        $query = "UPDATE tasks SET is_done = ? WHERE id = ?";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$isDone ? 1 : 0, $id])) {
            return QueryResult::fail();
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }

    public function updateTask(int $id, string $title, string $description, bool $isDone): QueryResult
    {
        $old = $this->getTaskByID($id);
        if (!$old) {
            throw new InvalidArgumentException("Task not found.");
        }

        if (
            $old['title'] === $title &&
            $old['description'] === $description &&
            (int)$old['is_done'] === $isDone
        ) {
            return QueryResult::ok($old->data, 0);
        }

        $query = "UPDATE tasks 
                SET title = ?, description = ?, is_done = ?
                WHERE id = ?";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$title, $description, $isDone ? 1 : 0, $id])) {
            return QueryResult::fail();
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }

    public function deleteTask(int $id): QueryResult
    {
        $query = "DELETE FROM tasks WHERE id = ?";

        $stmt = $this->pdo->prepare($query);
        if (!$stmt->execute([$id])) {
            return QueryResult::fail();
        }

        return QueryResult::ok(null, $stmt->rowCount());
    }
}
