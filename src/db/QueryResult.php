<?php

class QueryResult
{
    public bool $success;
    public int $affected;
    public mixed $data;

    private function __construct(bool $success, int $affected = 0, mixed $data = null)
    {
        $this->success = $success;
        $this->affected = $affected;
        $this->data = $data;
    }

    public static function ok(mixed $data = null, int $affected = 0): self
    {
        return new self(true, $affected, $data);
    }

    public static function fail(): self
    {
        return new self(false, 0, null);
    }

    public function isChanged(): bool
    {
        return $this->affected > 0;
    }
}
