<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Request;

/**
 * Trait TestHelperTrait
 *
 * Provides helper methods for creating Request objects
 * during API-related unit tests.
 *
 * This trait helps to quickly generate Request instances
 * with customizable HTTP method, path, query parameters,
 * route params, and request body.
 *
 * @package Tests\Unit\Api
 */
trait TestHelperTrait
{
    /**
     * Creates a mock Request instance with optional data.
     *
     * Simplifies setup for tests that require constructing
     * different request types (e.g., GET, POST) with various
     * query or body parameters.
     *
     * @param array<string, mixed>  $body    Optional JSON body content.
     * @param array<string, mixed>  $query   Optional query parameters.
     * @param array<string, mixed>  $params  Optional route parameters.
     * @param string                $method  HTTP method (default: 'POST').
     * @param string                $path    Request path (default: '/').
     *
     * @return Request A fully initialized Request instance.
     */
    private function makeRequest(
        array $body = [],
        array $query = [],
        array $params = [],
        string $method = 'POST',
        string $path = '/'
    ): Request {
        // Encode body to JSON if provided
        $rawInput = !empty($body) ? json_encode($body) : null;
        if ($rawInput === false) {
            $rawInput = null;
        }

        // Instantiate the Request with named arguments for clarity
        $req = new Request(
            method: $method,
            path: $path,
            query: $query,
            rawInput: $rawInput
        );

        // Attach route parameters if any are provided
        if (!empty($params)) {
            $req->params = $params;
        }

        // Return the constructed Request for test use
        return $req;
    }
}
