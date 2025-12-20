<?php

declare(strict_types=1);

namespace App\Utils;

use App\Api\Request;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class RequestValidator
 *
 * Provides type-safe extraction and validation helpers for data contained
 * in a {@see Request} object. Ensures strict verification of integers,
 * strings, booleans, and emails, while unifying access logic across
 * route params, query parameters, and request body fields.
 *
 * - Centralizes request value lookup (params → query → body)
 * - Enforces strict type and format validation
 * - Provides consistent error handling via exceptions
 * - Helps normalize boolean and numeric evaluation across sources
 *
 * @package App\Utils
 */
class RequestValidator
{
    /**
     * Locate raw value by searching parameters in priority order:
     *
     * 1. Route parameters
     * 2. Query string parameters
     * 3. Request body values
     *
     * @param Request $req  Incoming request instance
     * @param string  $key  Parameter key to search for
     *
     * @return mixed|null Raw value or null if not found
     */
    private static function findRaw(Request $req, string $key): mixed
    {
        return $req->getParam($key)
            ?? $req->getQuery($key)
            ?? $req->getBodyValue($key);
    }

    /**
     * Retrieve an integer parameter.
     *
     * - Accepts numeric scalar values only
     * - Rejects non-digit strings, arrays, objects, etc.
     *
     * @param Request $req
     * @param string  $key       Parameter key
     * @param string  $errorMsg  Error message if validation fails
     *
     * @return int
     *
     * @throws InvalidArgumentException If value is not a valid integer
     */
    public static function getInt(Request $req, string $key, string $errorMsg): int
    {
        $val = self::findRaw($req, $key);

        // Validate integer type (must be scalar digits only)
        if (!is_scalar($val) || !ctype_digit((string) $val)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return (int) $val;
    }

    /**
     * Retrieve a non-empty string parameter.
     *
     * - Must be scalar
     * - HTML tags are stripped
     * - Empty or whitespace-only strings are rejected
     *
     * @param Request $req
     * @param string  $key
     * @param string  $errorMsg
     *
     * @return string
     *
     * @throws InvalidArgumentException If value is invalid or empty
     */
    public static function getString(Request $req, string $key, string $errorMsg): string
    {
        $rawVal = self::findRaw($req, $key);

        if (!is_scalar($rawVal)) {
            throw new InvalidArgumentException($errorMsg);
        }

        $val = trim(strip_tags((string) $rawVal));
        if ($val === '') {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    /**
     * Retrieve and validate an email address.
     *
     * - Must be scalar
     * - Strips HTML tags
     * - Must pass FILTER_VALIDATE_EMAIL
     *
     * @param Request $req
     * @param string  $key
     * @param string  $errorMsg
     *
     * @return string Valid email string
     *
     * @throws InvalidArgumentException If value is invalid or malformed
     */
    public static function getEmail(Request $req, string $key, string $errorMsg): string
    {
        $rawVal = self::findRaw($req, $key);

        if (!is_scalar($rawVal)) {
            throw new InvalidArgumentException($errorMsg);
        }

        $val = trim(strip_tags((string) $rawVal));

        if ($val === '' || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    /**
     * Retrieve a boolean parameter.
     *
     * Supports:
     * - Real booleans
     * - Numeric booleans (0,1)
     * - String forms: "true", "false", "1", "0"
     *
     * @param Request $req
     * @param string  $key
     * @param string  $errorMsg
     * @param bool    $normalizeInvalid  If true, invalid values become false instead of throwing
     *
     * @return bool
     *
     * @throws InvalidArgumentException If validation fails and $normalizeInvalid is false
     */
    public static function getBool(Request $req, string $key, string $errorMsg, bool $normalizeInvalid = false): bool
    {
        // Retrieve and validate presence
        $value = self::findRaw($req, $key);
        if ($value === null) {
            throw new InvalidArgumentException($errorMsg);
        }

        // PHPStan type assertion: $value is non-null from this point
        /** @var non-empty-string|int|float|bool|array<mixed> $value */

        // Handle direct boolean
        if (is_bool($value)) {
            return $value;
        }

        // Handle numeric values (0 or 1)
        if (is_numeric($value)) {
            $asInt = (int) $value;
            if ($asInt === 0) {
                return false;
            }
            if ($asInt === 1) {
                return true;
            }
        }

        // Handle string representations
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            switch ($normalized) {
                case 'true':
                case '1':
                    return true;
                case 'false':
                case '0':
                    return false;
            }
        }

        // Handle invalid values
        if ($normalizeInvalid) {
            return false;
        }

        throw new InvalidArgumentException($errorMsg);
    }

    /**
     * Retrieve an array parameter.
     *
     * @param Request $req
     * @param string  $key
     * @param string  $errorMsg
     *
     * @return array<mixed>
     *
     * @throws InvalidArgumentException If value is not an array
     */
    public static function getArray(Request $req, string $key, string $errorMsg): array
    {
        $val = self::findRaw($req, $key);

        if (!is_array($val)) {
            throw new InvalidArgumentException($errorMsg);
        }

        return $val;
    }

    /**
     * Retrieve the authenticated user ID from the request context.
     *
     * - Expects $req->auth['id'] to be set by middleware
     * - Throws RuntimeException if not found (should be caught by middleware/framework)
     *
     * @param Request $req
     * @return string
     * @throws RuntimeException If user ID is missing or invalid
     */
    public static function getAuthUserId(Request $req): string
    {
        if (!isset($req->auth['id']) || !is_string($req->auth['id'])) {
            throw new RuntimeException('Authenticated user ID not found in request context.');
        }

        return $req->auth['id'];
    }

    /**
     * Ensure that a given operation result denotes success.
     *
     * Validates:
     * - `$result->success` exists and is true
     * - If `$ignoreChanges` is false: requires `isChanged()` to return true
     * - If `$requireData` is true: ensures `data` exists and is not null
     *
     * @param object $result       Result object from a service or repository
     * @param string $action       Action description (for error context)
     * @param bool   $requireData  If true, requires non-null `data`
     * @param bool   $ignoreChanges If true, skip change detection
     *
     * @return void
     *
     * @throws RuntimeException If validation fails
     */
    public static function ensureSuccess(object $result, string $action, bool $requireData = true, bool $ignoreChanges = false): void
    {
        // result must have a boolean `success` flag
        if (!isset($result->success) || !is_bool($result->success)) {
            throw new RuntimeException("Invalid result object passed to ensureSuccess");
        }

        // failure case
        if (!$result->success) {
            $errorInfo = isset($result->error) && is_array($result->error)
                ? implode(' | ', $result->error)
                : 'Unknown database error.';
            throw new RuntimeException("Failed to {$action}: {$errorInfo}");
        }

        // check if any change occurred
        if (!$ignoreChanges && method_exists($result, 'isChanged') && !$result->isChanged()) {
            throw new RuntimeException("Failed to {$action}: No data or changes found.");
        }

        // ensure result contains data when required
        if ($requireData && (!property_exists($result, 'data') || $result->data === null)) {
            throw new RuntimeException("Failed to {$action}: No data or changes found.");
        }
    }

}
