<?php

declare(strict_types=1);

namespace App\DB;

/**
 * QueryResult represents the outcome of a database query.
 */
class QueryResult
{
    // Indicates whether the query was successful
    public bool $success;

    // Number of rows affected by the query
    public int $affected;

    // Data returned from the query, if any
    public mixed $data;

    // Optional array of error information
    public ?array $error;

    /**
     * Private constructor to enforce use of static factory methods
     *
     * @param bool $success  Query success flag
     * @param int $affected  Number of affected rows
     * @param mixed $data    Query result data
     * @param array|null $error Optional error details
     */
    private function __construct(
        bool $success,
        int $affected = 0,
        mixed $data = null,
        ?array $error = null
    ) {
        $this->success = $success;
        $this->affected = $affected;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * Factory method for a successful query result
     *
     * @param mixed $data    Optional data returned from query
     * @param int $affected  Number of affected rows
     * @return self
     */
    public static function ok(mixed $data = null, int $affected = 0): self
    {
        return new self(true, $affected, $data, null);
    }

    /**
     * Factory method for a failed query result
     *
     * @param array|null $error Optional error details
     * @return self
     */
    public static function fail(?array $error = null): self
    {
        return new self(false, 0, null, $error);
    }

    /**
     * Check if the query changed any rows
     *
     * @return bool True if affected rows > 0
     */
    public function isChanged(): bool
    {
        return $this->affected > 0;
    }

    /**
     * Check if the query returned any data
     *
     * @return bool True if data is not empty
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }
}
