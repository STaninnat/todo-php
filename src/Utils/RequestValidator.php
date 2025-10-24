<?php

declare(strict_types=1);

namespace App\Utils;

use App\Api\Request;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class RequestValidator
 *
 * Utility class providing static methods to validate and extract parameters
 * from a {@see Request} object. Supports type-safe validation for integers,
 * strings, booleans, and emails, and ensures proper error handling.
 *
 * @package App\Utils
 */
class RequestValidator
{
    /**
     * Retrieve and validate an integer parameter from request input sources.
     *
     * - Checks param, query, and body in order
     * - Ensures the value is numeric and composed of digits only
     * - Throws an exception if invalid
     *
     * @param Request $req      The request instance
     * @param string  $key      Parameter key to look for
     * @param string  $errorMsg Error message to throw if invalid
     *
     * @return int Validated integer value
     *
     * @throws InvalidArgumentException If parameter is missing or invalid
     */
    public static function getIntParam(Request $req, string $key, string $errorMsg): int
    {
        // Fetch parameter from request (param → query → body)
        $val = $req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? null;

        // Validate integer type (must be scalar digits only)
        if (!is_scalar($val) || !ctype_digit((string)$val)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return (int)$val;
    }

    /**
     * Retrieve and validate a string parameter.
     *
     * - Sanitizes using trim and strip_tags
     * - Ensures the result is a non-empty string
     *
     * @param Request $req      The request instance
     * @param string  $key      Parameter key to look for
     * @param string  $errorMsg Error message to throw if invalid
     *
     * @return string Sanitized and validated string value
     *
     * @throws InvalidArgumentException If missing or empty
     */
    public static function getStringParam(Request $req, string $key, string $errorMsg): string
    {
        // Extract and sanitize parameter value
        $rawVal = $req->getParam($key) ?? $req->getQuery($key) ?? ($req->body[$key] ?? null);

        if (!is_scalar($rawVal)) {
            throw new InvalidArgumentException($errorMsg);
        }

        $val = trim(strip_tags((string)$rawVal));
        if ($val === '') {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    /**
     * Retrieve and validate an email parameter.
     *
     * - Trims and strips tags
     * - Uses PHP’s built-in email validation filter
     *
     * @param Request $req      The request instance
     * @param string  $key      Parameter key to look for
     * @param string  $errorMsg Error message to throw if invalid
     *
     * @return string Validated email address
     *
     * @throws InvalidArgumentException If invalid or missing
     */
    public static function getEmailParam(Request $req, string $key, string $errorMsg): string
    {
        $rawVal = $req->getParam($key) ?? $req->getQuery($key) ?? ($req->body[$key] ?? null);

        if (!is_scalar($rawVal)) {
            throw new InvalidArgumentException($errorMsg);
        }

        $val = trim(strip_tags((string)$rawVal));

        if ($val === '' || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    /**
     * Retrieve and validate a boolean parameter.
     *
     * - Accepts numeric boolean values (0 or 1)
     * - Returns false by default if invalid
     *
     * @param Request $req      The request instance
     * @param string  $key      Parameter key to look for
     * @param string  $errorMsg Error message to throw if missing
     *
     * @return bool Boolean value (true for 1, false for 0 or invalid)
     *
     * @throws InvalidArgumentException If parameter is missing
     */
    public static function getBoolParam(Request $req, string $key, string $errorMsg): bool
    {
        $val = $req->getParam($key) ?? $req->getQuery($key) ?? $req->body[$key] ?? null;
        if ($val === null) {
            throw new InvalidArgumentException($errorMsg);
        }

        // Validate numeric boolean representation
        if (!is_scalar($val) || !ctype_digit((string)$val) || !in_array((int)$val, [0, 1], true)) {
            return false; // Default to false if invalid
        }

        return (bool)((int)$val);
    }

    /**
     * Ensure that a given operation result indicates success.
     *
     * - Verifies that the result object has `success` and `isChanged()` true
     * - Throws a runtime exception if the operation failed or did not modify data
     *
     * @param object $result Object with `success`, `isChanged()`, and `error` properties
     * @param string $action Action description for error message context
     *
     * @return void
     *
     * @throws RuntimeException If the operation failed or made no changes
     */
    public static function ensureSuccess(object $result, string $action): void
    {
        // Check required properties/methods exist
        if (!isset($result->success) || !is_bool($result->success) || !is_callable([$result, 'isChanged'])) {
            throw new RuntimeException("Invalid result object passed to ensureSuccess");
        }

        if (!$result->success || !$result->isChanged()) {
            $errorInfo = 'No changes were made.';

            if (isset($result->error) && is_array($result->error)) {
                $errorInfo = implode(' | ', $result->error);
            }

            throw new RuntimeException("Failed to {$action}: {$errorInfo}");
        }
    }
}
