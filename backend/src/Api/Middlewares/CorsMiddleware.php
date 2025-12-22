<?php

declare(strict_types=1);

namespace App\Api\Middlewares;

use App\Api\Request;

/**
 * Class CorsMiddleware
 *
 * Handles Cross-Origin Resource Sharing (CORS) headers.
 * Allows the frontend (running on a different domain) to access the API.
 *
 * @package App\Api\Middlewares
 */
class CorsMiddleware
{
    /**
     * List of allowed origins.
     *
     * @var array<string>
     */
    private array $allowedOrigins;

    /**
     * Initializes the middleware with a list of allowed origins.
     *
     * @param array<string> $allowedOrigins List of origins that are allowed to access the API.
     *
     * @return void
     */
    public function __construct(array $allowedOrigins = [])
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * Handle the request and inject CORS headers.
     *
     * @param Request $request
     *
     * @return void
     */
    public function __invoke(Request $request): void
    {
        /** @var string $origin */
        $origin = $_SERVER['HTTP_ORIGIN'] ?? 'null';

        // If no specific origins defined, allow all (or check against list)
        // For security, ideally we match against whitelist.
        // If allowedOrigins is empty, we default to allowing all (useful for dev/demos)
        // OR we can strictly require it.

        $allowOrigin = '*';
        if (!empty($this->allowedOrigins)) {
            if (in_array($origin, $this->allowedOrigins)) {
                $allowOrigin = $origin;
            } else {
                // If origin not allowed, we can either block or just return * (if appropriate)
                // Returning the specific origin is safer than * for credentials.
                $allowOrigin = 'null'; // Block
            }
        } else {
            // If $origin is present, echo it back to allow credentials
            if ($origin) {
                $allowOrigin = $origin;
            }
        }

        header("Access-Control-Allow-Origin: " . (string) $allowOrigin);
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // Cache preflight for 1 day

        // Handle preflight OPTIONS request specifically
        if ($request->method === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
    }
}
