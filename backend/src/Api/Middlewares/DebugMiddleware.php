<?php

declare(strict_types=1);

namespace App\Api\Middlewares;

use App\Api\Request;
use App\Utils\Logger;

/**
 * Class DebugMiddleware
 *
 * Middleware used to log structured request information for debugging.
 * Captures HTTP method, path, query parameters, parsed body content,
 * route parameters, and JSON decoding errors if present.
 *
 * - Helpful for tracing request flow in development
 * - Logs normalized data as processed by {@see Request}
 * - Includes JSON error reporting when raw input decoding fails
 *
 * @package App\Api\Middlewares
 */
class DebugMiddleware
{
    /** @var Logger Logger instance used for writing debug output */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param Logger $logger Logging service used to emit structured debug details
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Invoke middleware for processing an incoming request.
     *
     * Logs:
     * - HTTP method and request path
     * - Normalized query parameters
     * - Parsed body array (JSON, form-data, or URL-encoded)
     * - Route parameters if present
     * - JSON decoding errors when detected
     *
     * @param Request $request Fully normalized request instance
     *
     * @return void
     */
    public function __invoke(Request $request): void
    {
        $this->logger->info("=== Debug Middleware ===");
        $this->logger->info("Request Method: {$request->method}");
        $this->logger->info("Request Path: {$request->path}");
        $this->logger->info("Query Params: " . json_encode($this->sanitize($request->query)));
        $this->logger->info("Body Params: " . json_encode($this->sanitize($request->body)));

        // Route parameters (if any)
        if (!empty($request->params)) {
            $this->logger->info("Route Params: " . json_encode($request->params));
        }

        // JSON parsing error diagnostic
        $jsonError = $request->getJsonError();
        if ($jsonError !== null) {
            $this->logger->warning("JSON Decode Error: {$jsonError}");
        }

        $this->logger->info("========================");
    }

    /**
     * Recursively masks sensitive fields in an array.
     *
     * @param array<mixed> $data Input data
     *
     * @return array<mixed> Sanitized data
     */
    private function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'authorization', 'cookie', 'session'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } elseif (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
