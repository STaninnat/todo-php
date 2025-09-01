<?php

namespace App\utils;

/**
 * Send a standardized JSON response and exit the script.
 *
 * @param bool $success Whether the response indicates success
 * @param string $type Response type ('success', 'error', 'info'); auto-set if empty
 * @param string $message Message describing the response
 * @param array $data Optional data payload
 * @param int|null $totalPages Optional total pages for pagination (default: 0)
 * @return void
 */
function jsonResponse(
    bool $success,
    string $type = '',
    string $message,
    array $data = [],
    ?int $totalPages = null
): void {
    // Set response content type to JSON
    header('Content-Type: application/json');

    // Auto-set type if not provided
    if (!$type) {
        $type = $success ? 'success' : 'error';
    }

    // Ensure type is one of the allowed values
    if (!in_array($type, ['success', 'error', 'info'], true)) {
        $type = 'info';
    }

    // Encode response as JSON and send
    echo json_encode([
        'success' => $success,
        'type' => $type,
        'message' => $message,
        'data' => $data,
        'totalPages' => $totalPages ?? 0
    ]);

    // Terminate script after sending response
    exit;
}
