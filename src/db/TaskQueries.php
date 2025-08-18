<?php

/**
 * Class Task
 *
 * Handles CRUD operations for tasks in the database.
 * Uses PDO for database interactions.
 *
 * @package todo-php
 * @author humblegod
 * @version 1.0
 */
class TaskQueries
{
    /**
     * @var PDO PDO instance for database connection
     */
    private $pdo;

    /**
     * Constructor to initialize PDO instance
     *
     * @param PDO $pdo A PDO database connection object
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Add a new task with title and description
     *
     * @param string $title Task title (cannot be empty)
     * @param string $description Task description (optional)
     * @throws InvalidArgumentException If title is empty after trimming
     * @return int|null ID of the newly inserted task, or null on failure
     */
    public function addTasks(string $title, string $description): ?int
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException("Title cannot be empty.");
        }

        try {
            $query = "INSERT INTO tasks (title, description) VALUES (?, ?)";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $description, PDO::PARAM_STR);
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding task: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve all tasks ordered by last updated timestamp descending
     *
     * @return array An array of associative arrays, each representing a task
     */
    public function getAllTasks(): array
    {
        try {
            $query = "SELECT * FROM tasks ORDER BY is_done ASC, updated_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $tasks;
        } catch (PDOException $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve a single task by its ID
     *
     * @param int $id Task ID to fetch
     * @return array|null Associative array of the task data, or null if not found
     */
    public function getTasksByID(int $id): ?array
    {
        try {
            $query = "SELECT * FROM tasks WHERE id = ? LIMIT 1";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            return $task === false ? null : $task;
        } catch (PDOException $e) {
            error_log("Error fetching task by id: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a paginated list of tasks.
     *
     * Tasks are ordered first by completion status (`is_done` ascending),
     * then by last updated time (`updated_at` descending).
     *
     * @param int $page Current page number (1-based)
     * @param int $perPage Number of tasks per page, default is 10
     * @return array Array of associative arrays representing tasks
     */
    public function getTasksByPage(int $page, int $perPage = 10): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $query = "SELECT * FROM tasks ORDER BY is_done ASC, updated_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the total number of tasks in the database.
     *
     * @return int Total count of tasks
     */
    public function getTotalTasks(): int
    {
        try {
            return (int) $this->pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting tasks: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a task as done or undone
     *
     * @param int $id Task ID to update
     * @param bool $isDone True to mark done, false to mark undone
     * @return bool True if the update affected at least one row, false otherwise
     */
    public function markDone(int $id, bool $isDone): bool
    {
        try {
            $isDoneInt = $isDone ? 1 : 0;

            $query = "UPDATE tasks SET is_done = ? WHERE id = ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(1, $isDoneInt, PDO::PARAM_INT);
            $stmt->bindParam(2, $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating task: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a task's title and description by its ID
     *
     * @param int $id Task ID to update
     * @param string $title New title (cannot be empty)
     * @param string $description New description
     * @param int $is_done New status (or same as before)
     * @throws InvalidArgumentException If title is empty after trimming
     * @return bool True if the update affected at least one row, false otherwise
     */
    public function updateTask(int $id, string $title, string $description, int $isDone): bool
    {
        $oldTask = $this->getTasksByID($id);
        if (!$oldTask) {
            throw new InvalidArgumentException("Task not found.");
        }

        if (
            $oldTask['title'] === $title &&
            $oldTask['description'] === $description &&
            (int)$oldTask['is_done'] === $isDone
        ) {
            return false;
        }

        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException("Title cannot be empty.");
        }

        $isDone === 1 ? 1 : 0;

        try {
            $query = "UPDATE tasks 
                SET title = ?, description = ?, is_done = ?
                WHERE id = ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(1, $title, PDO::PARAM_STR);
            $stmt->bindParam(2, $description, PDO::PARAM_STR);
            $stmt->bindParam(3, $isDone, PDO::PARAM_INT);
            $stmt->bindParam(4, $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating task: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a task by its ID
     *
     * @param int $id Task ID to delete
     * @return bool True if a task was deleted, false otherwise
     */
    public function deleteTask(int $id): bool
    {
        try {
            $query = "DELETE FROM tasks WHERE id = ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting task: " . $e->getMessage());
            return false;
        }
    }
}
