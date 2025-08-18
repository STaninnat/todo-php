<?php

/**
 * Send JSON response as standardized
 *
 * @param bool $success
 * @param string $message
 * @param array $data
 * @param int|null $totalPages
 * @param string $type success|error
 */
function jsonResponse(
    bool $success,
    string $type = '',
    string $message,
    array $data = [],
    ?int $totalPages = null
): void {
    header('Content-Type: application/json');

    if (!$type) {
        $type = $success ? 'success' : 'error';
    }

    if (!in_array($type, ['success', 'error', 'info'], true)) {
        $type = 'info';
    }

    echo json_encode([
        'success' => $success,
        'type' => $type,
        'message' => $message,
        'data' => $data,
        'totalPages' => $totalPages ?? 0
    ]);

    exit;
}
