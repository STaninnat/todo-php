<?php

declare(strict_types=1);

namespace App\Utils;

use App\Api\Request;
use InvalidArgumentException;
use RuntimeException;

class RequestValidator
{
    public static function getIntParam(Request $req, string $key, string $errorMsg): int
    {
        $val = $req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? null;

        if (!is_scalar($val) || !ctype_digit((string)$val)) {
            throw new InvalidArgumentException($errorMsg);
        }


        return (int)$val;
    }

    public static function getStringParam(Request $req, string $key, string $errorMsg): string
    {
        $val = trim(strip_tags($req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? ''));

        if (!is_string($val)) {
            throw new InvalidArgumentException($errorMsg);
        }

        $val = trim(strip_tags($val));

        if ($val === '') {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    public static function getEmailParam(Request $req, string $key, string $errorMsg): string
    {
        $val = trim(strip_tags($req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? ''));
        if ($val === '' || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    public static function getBoolParam(Request $req, string $key, string $errorMsg): bool
    {
        $val = $req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? null;
        if ($val === null) {
            throw new InvalidArgumentException($errorMsg);
        }

        if (!ctype_digit((string)$val) || !in_array((int)$val, [0, 1], true)) {
            return false; // default to 0
        }

        return (bool)((int)$val);
    }

    public static function ensureSuccess(object $result, string $action): void
    {
        if (!$result->success || !$result->isChanged()) {
            $errorInfo = $result->error ? implode(' | ', $result->error) : 'No changes were made.';
            throw new RuntimeException("Failed to {$action}: {$errorInfo}");
        }
    }
}
