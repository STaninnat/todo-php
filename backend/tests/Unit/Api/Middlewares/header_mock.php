<?php

namespace App\Api\Middlewares;

/**
 * Header Mock Helper
 *
 * Provides a mocked version of the native PHP `header()` function
 * within the App\Api\Middlewares namespace. This allows unit tests
 * to capture and verify headers sent by middleware without relying
 * on valid SAPI or output buffering.
 *
 * @package Tests\Unit\Api\Middlewares
 */

/**
 * Mock global header function for testing.
 *
 * Captures the header string into a global array for later verification.
 *
 * @param string $string             The header string (e.g., "Location: /")
 * @param bool   $replace            Whether to replace previous similar headers
 * @param int    $http_response_code Optional HTTP response code
 *
 * @return void
 */
function header(string $string, bool $replace = true, int $http_response_code = 0): void
{
    \Tests\Unit\Api\Middlewares\x_track_headers($string);
}

namespace Tests\Unit\Api\Middlewares;

/**
 * @var array<string> $capturedHeaders Stores headers captured during tests
 */
$capturedHeaders = [];

/**
 * Tracks headers sent during tests
 *
 * @param string $header The header string to track
 *
 * @return void
 */
function x_track_headers(string $header): void
{
    global $capturedHeaders;
    /** @var array<string> $capturedHeaders */
    $capturedHeaders[] = $header;
}

/**
 * Retrieves the captured headers for verification
 *
 * @return array<string> The captured headers
 */
function xdebug_get_headers(): array
{
    global $capturedHeaders;
    /** @var array<string> $capturedHeaders */
    return $capturedHeaders;
}

/**
 * Clears all headers captured during the test.
 *
 * Resets the global $capturedHeaders array to an empty state.
 *
 * @return void
 */
function x_cleanup_headers(): void
{
    global $capturedHeaders;
    $capturedHeaders = [];
}
