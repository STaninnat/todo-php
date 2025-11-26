<?php

declare(strict_types=1);

namespace App\Api;

/**
 * Class Request
 *
 * Represents an HTTP request abstraction that normalizes data from
 * PHP superglobals, raw input, and uploaded files. Provides convenient
 * accessors for HTTP method, path, query, body, route parameters, and files.
 *
 * - Supports parsing JSON, URL-encoded, and form-data bodies
 * - Provides helper methods for retrieving typed parameters safely
 * - Handles uploaded file access
 *
 * @package App\Api
 */
class Request
{
    /** @var string HTTP method (e.g. GET, POST, PUT, DELETE) */
    public string $method;

    /** @var string Request path (e.g. /api/v1/users) */
    public string $path;

    /** @var array<string, mixed> Query parameters from $_GET */
    public array $query;

    /** @var array<string, mixed> Parsed request body */
    public array $body;

    /** @var array<string, mixed> Route parameters (typically set by router) */
    public array $params = [];

    /** @var array<string, mixed>|null Authentication information if provided */
    public ?array $auth = null;

    /** 
     * @var array<string, array{
     *     name: string,
     *     type: string,
     *     tmp_name: string,
     *     error: int,
     *     size: int
     * }>
     */
    public array $files = [];

    /** @var array<string, mixed>|null Decoded JSON array from raw input, null if not an array */
    private ?array $decodedJson = null;

    /** @var string|null Stores last JSON parsing error message */
    private ?string $jsonError = null;

    /**
     * Constructor
     *
     * Initializes request properties by normalizing HTTP method, path, query,
     * body, and uploaded files. Body is parsed from JSON, URL-encoded string,
     * or fallback POST array.
     *
     * @param string|null               $method   HTTP method (defaults to $_SERVER['REQUEST_METHOD'])
     * @param string|null               $path     Request path (defaults to $_SERVER['REQUEST_URI'])
     * @param array<string, mixed>|null $query    Query parameters (defaults to $_GET)
     * @param string|null               $rawInput Raw input for JSON or URL-encoded parsing
     * @param array<string, mixed>|null $post     Fallback POST array (defaults to $_POST)
     * @param array<string, mixed>|null $files    Uploaded files array (defaults to $_FILES)
     */
    public function __construct(
        ?string $method = null,
        ?string $path = null,
        ?array $query = null,
        ?string $rawInput = null,
        ?array $post = null,
        ?array $files = null
    ) {
        // Normalize HTTP method (default to GET)
        $methodValue = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = strtoupper(is_string($methodValue) ? $methodValue : 'GET');

        // Extract and normalize request path
        $uriValue = $path ?? $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url(is_string($uriValue) ? $uriValue : '/', PHP_URL_PATH) ?: '/';

        // Initialize query parameters
        $this->query = $this->normalizeArray($query ?? $_GET);

        // Parse body (attempt JSON, URL-encoded, or fallback to form-data)
        $raw = $rawInput ?? file_get_contents('php://input');
        $postArray = $this->normalizeArray($post ?? $_POST);
        $this->body = $this->parseBody((string)$raw, $postArray);

        //Files
        $filesInputRaw = $files ?? $_FILES;
        /** @var array<string, mixed> $filesInput */
        $filesInput = $this->normalizeArray($filesInputRaw);
        $this->files = $this->normalizeFiles($filesInput);
    }

    /**
     * Parse raw request body into an associative array.
     *
     * - Attempts JSON decoding first
     * - Falls back to parsing URL-encoded strings
     * - Defaults to $_POST if no valid data found
     *
     * @param string                $raw  Raw request body
     * @param array<string, mixed>  $post POST data fallback
     *
     * @return array<string, mixed> Parsed body content
     */
    private function parseBody(string $raw, array $post = []): array
    {
        if ($raw !== '') {
            // Try parse as JSON
            $json = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->jsonError = json_last_error_msg();
            } else {
                $this->jsonError = null;
            }

            if (is_array($json)) {
                $this->decodedJson = $this->normalizeArray($json);
                return $this->decodedJson;
            }

            // Try parse as URL-encoded only if string contains '='

            if (str_contains($raw, '=')) {
                parse_str($raw, $parsed);

                /** @phpstan-ignore-next-line */
                if (is_array($parsed)) {
                    return $this->normalizeArray($parsed);
                }
            }
        }

        // fallback: form-data ($_POST)
        return $this->normalizeArray($post);
    }

    /**
     * Normalize array keys to string and values to mixed (PHPStan-friendly)
     *
     * @param array<mixed> $array
     * 
     * @return array<string, mixed>
     */
    private function normalizeArray(array $array): array
    {
        $result = [];
        foreach ($array as $k => $v) {
            $result[(string)$k] = $v;
        }

        return $result;
    }

    /**
     * Normalize uploaded files to strict array-shape.
     *
     * @param array<string, mixed> $files
     * 
     * @return array<string, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function normalizeFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $file) {

            if (!is_array($file)) {
                continue;
            }

            if (
                isset($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']) &&
                is_string($file['name']) &&
                is_string($file['type']) &&
                is_string($file['tmp_name']) &&
                is_int($file['error']) &&
                is_int($file['size'])
            ) {
                $result[(string)$key] = [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'tmp_name' => $file['tmp_name'],
                    'error' => $file['error'],
                    'size' => $file['size'],
                ];
            }
        }

        return $result;
    }

    /**
     * Get full parsed body array.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Get decoded JSON array from raw input, or null if not an array.
     *
     * @return array<string, mixed>|null
     */
    public function getJson(): ?array
    {
        return $this->decodedJson;
    }

    /**
     * Get last JSON parsing error message.
     *
     * @return string|null JSON error message or null if last decode succeeded
     */
    public function getJsonError(): ?string
    {
        return $this->jsonError;
    }

    // ==== Typed Getters (Query) ====

    /**
     * Retrieve a query parameter (from $query).
     *
     * @param string $key     Parameter name
     * @param mixed  $default Default value if not found
     *
     * @return mixed Query parameter value or default
     */
    public function getQuery(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Retrieve query parameter as int.
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    public function getIntQuery(string $key, int $default = 0): int
    {
        $v = $this->query[$key] ?? null;
        return is_numeric($v) ? (int)$v : $default;
    }

    /**
     * Retrieve query parameter as string.
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function getStringQuery(string $key, string $default = ''): string
    {
        $v = $this->query[$key] ?? null;
        return is_scalar($v) ? (string)$v : $default;
    }

    /**
     * Retrieve query parameter as bool.
     *
     * @param string $key
     * @param bool   $default
     *
     * @return bool
     */
    public function getBoolQuery(string $key, bool $default = false): bool
    {
        if (!isset($this->query[$key])) {
            return $default;
        }

        return filter_var($this->query[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    // ==== Typed Getters (Body) ====

    /**
     * Retrieve raw body value by key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getBodyValue(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Retrieve body parameter as int.
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    public function getIntBody(string $key, int $default = 0): int
    {
        $v = $this->body[$key] ?? null;
        return is_numeric($v) ? (int)$v : $default;
    }

    /**
     * Retrieve body parameter as string.
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function getStringBody(string $key, string $default = ''): string
    {
        $v = $this->body[$key] ?? null;
        return is_scalar($v) ? (string)$v : $default;
    }

    /**
     * Retrieve body parameter as bool.
     *
     * @param string $key
     * @param bool   $default
     *
     * @return bool
     */
    public function getBoolBody(string $key, bool $default = false): bool
    {
        if (!isset($this->body[$key])) {
            return $default;
        }

        return filter_var($this->body[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    // ==== Route Params ====

    /**
     * Retrieve a route parameter (from $params).
     *
     * @param string $key     Parameter name
     * @param mixed  $default Default value if not found
     *
     * @return mixed Parameter value or default
     */
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    // ==== File Handling ====

    /**
     * Check if a file exists and was uploaded successfully.
     *
     * @param string $key File key
     *
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
            is_uploaded_file($this->files[$key]['tmp_name']);
    }

    /**
     * Get file array by key, or null if missing.
     *
     * @param string $key File key
     *
     * @return array<string, mixed>|null
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }
}
