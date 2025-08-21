<?php

class QueryResult
{
    public bool $success;
    public int $affected;
    public mixed $data;
    public ?array $error;

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

    public static function ok(mixed $data = null, int $affected = 0): self
    {
        return new self(true, $affected, $data, null);
    }

    public static function fail(?array $error = null): self
    {
        return new self(false, 0, null, $error);
    }

    public static function empty(): self
    {
        return new self(true, 0, null, null);
    }

    public function isChanged(): bool
    {
        return $this->affected > 0;
    }
}
