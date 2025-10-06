<?php

declare(strict_types=1);

namespace App\Api;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $params = [];
    public ?array $auth = null;

    public function __construct(
        ?string $method = null,
        ?string $path = null,
        ?array $query = null,
        ?string $rawInput = null,
        ?array $post = null
    ) {
        $this->method = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path   = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->query  = $query ?? $_GET ?? [];

        $raw = $rawInput ?? file_get_contents("php://input");
        $this->body = $this->parseBody($raw, $post ?? $_POST);
    }

    private function parseBody(string $raw, array $post = []): array
    {
        if ($raw !== null && $raw !== '') {
            // Try parse as JSON
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }

            // Try parse as URL-encoded only if string contains '='
            if (str_contains($raw, '=')) {
                parse_str($raw, $parsed);
                if (!empty($parsed)) {
                    return $parsed;
                }
            }
        }

        // fallback: form-data ($_POST)
        return $post ?? [];
    }

    // helper methods
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getQuery(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
}
