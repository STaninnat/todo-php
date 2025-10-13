<?php

declare(strict_types=1);

namespace App\Api;

/**
 * Class Request
 *
 * Represents an HTTP request abstraction that normalizes data from
 * PHP superglobals and raw input sources. Provides convenient accessors
 * for HTTP method, path, query, body, and route parameters.
 *
 * - Supports parsing JSON, URL-encoded, and form-data bodies
 * - Provides helper methods for retrieving parameters safely
 *
 * @package App\Api
 */
class Request
{
    /** @var string HTTP method (e.g. GET, POST, PUT, DELETE) */
    public string $method;

    /** @var string Request path (e.g. /api/v1/users) */
    public string $path;

    /** @var array Query parameters from $_GET */
    public array $query;

    /** @var array Parsed request body */
    public array $body;

    /** @var array Route parameters (typically set by router) */
    public array $params = [];

    /** @var array|null Authentication information if provided */
    public ?array $auth = null;

    /**
     * Constructor
     *
     * Initializes the request by normalizing HTTP method, path, query,
     * and parsing body content from raw input or $_POST.
     *
     * @param string|null $method   HTTP method (defaults to $_SERVER['REQUEST_METHOD'])
     * @param string|null $path     Request path (defaults to $_SERVER['REQUEST_URI'])
     * @param array|null  $query    Query parameters (defaults to $_GET)
     * @param string|null $rawInput Raw input (for JSON or URL-encoded parsing)
     * @param array|null  $post     Fallback POST array (defaults to $_POST)
     */
    public function __construct(
        ?string $method = null,
        ?string $path = null,
        ?array $query = null,
        ?string $rawInput = null,
        ?array $post = null
    ) {
        // Normalize HTTP method (default to GET)
        $this->method = strtoupper($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Extract and normalize request path
        $this->path   = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Initialize query parameters
        $this->query  = $query ?? $_GET ?? [];

        // Parse body (attempt JSON, URL-encoded, or fallback to form-data)
        $raw = $rawInput ?? file_get_contents("php://input");
        $this->body = $this->parseBody($raw, $post ?? $_POST);
    }

    /**
     * Parse raw request body into an associative array.
     *
     * - Attempts JSON decoding first
     * - Falls back to parsing URL-encoded strings
     * - Defaults to $_POST if no valid data found
     *
     * @param string $raw  Raw request body
     * @param array  $post POST data fallback
     *
     * @return array Parsed body content
     */
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
}
