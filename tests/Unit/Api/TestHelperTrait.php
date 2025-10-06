<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\Request;

trait TestHelperTrait
{
    private function makeRequest(
        array $body = [],
        array $query = [],
        array $params = [],
        string $method = 'POST',
        string $path = '/'
    ): Request {
        $rawInput = !empty($body) ? json_encode($body) : null;

        $req = new Request(
            method: $method,
            path: $path,
            query: $query,
            rawInput: $rawInput
        );

        if (!empty($params)) {
            $req->params = $params;
        }

        return $req;
    }
}
