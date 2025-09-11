<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * JsonResponder
 *
 * A hybrid fluent JSON responder for API responses.
 * Provides:
 *  - Simple static constructors for success, error, info responses.
 *  - Fluent setters to modify response data, type, and HTTP status.
 *  - Quick shortcuts for immediate output.
 *  - CLI-safe send method for testing environments.
 *
 * Example usage:
 *   JsonResponder::success("Operation completed")->withData($data)->send();
 *   JsonResponder::quickError("Something went wrong");
 */
class JsonResponder
{
    private bool $success;
    private string $message;
    private string $type;
    private ?array $data = null;
    private ?int $totalPages = null;
    private int $httpStatus;

    /**
     * Private constructor to enforce static fluent creation
     *
     * @param bool $success
     * @param string $message
     * @param string $type Optional, default based on success/error
     * @param int|null $httpStatus Optional HTTP status code
     */
    private function __construct(bool $success, string $message, string $type = '', ?int $httpStatus = null)
    {
        $this->success = $success;
        $this->message = $message;

        // Determine type if not explicitly provided
        if ($type === '') {
            $type = $success ? 'success' : 'error';
        }

        // Only allow valid types
        if (!in_array($type, ['success', 'error', 'info'], true)) {
            $type = 'info';
        }
        $this->type = $type;

        // Default HTTP status: 200 for success, 400 for error
        $this->httpStatus = $httpStatus ?? ($success ? 200 : 400);
    }

    // -------------------------------------------------------------------------
    // Static constructors (Fluent interface)
    // -------------------------------------------------------------------------

    /**
     * Create a success response.
     *
     * @param string $message The success message.
     * @param string $type The response type.
     * @param int|null $httpStatus Optional HTTP status code.
     *
     * @return self
     */
    public static function success(string $message, string $type = '', ?int $httpStatus = null): self
    {
        return new self(true, $message, $type, $httpStatus);
    }

    /**
     * Create an error response.
     *
     * @param string $message The error message.
     * @param string $type The response type.
     * @param int|null $httpStatus Optional HTTP status code.
     *
     * @return self
     */
    public static function error(string $message, string $type = '', ?int $httpStatus = null): self
    {
        return new self(false, $message, $type, $httpStatus);
    }

    /**
     * Create an info response.
     *
     * @param string $message The info message.
     * @param string $type The response type (default 'info').
     * @param int|null $httpStatus Optional HTTP status code.
     *
     * @return self
     */
    public static function info(string $message, string $type = '', ?int $httpStatus = null): self
    {
        return new self(false, $message, $type === '' ? 'info' : $type, $httpStatus);
    }

    // -------------------------------------------------------------------------
    // Quick shortcuts for immediate response (bypass fluent chaining)
    // -------------------------------------------------------------------------

    /**
     * Create a quick success response.
     *
     * @param string $message The success message.
     * @param bool $exitAfter Whether to exit immediately after sending the response.
     * @param bool $forTest Whether the response is for testing.
     *
     * @return array
     */
    public static function quickSuccess(string $message, bool $exitAfter = true, bool $forTest = false): array
    {
        return self::success($message)->send($exitAfter, $forTest);
    }

    /**
     * Create a quick error response.
     *
     * @param string $message The error message.
     * @param bool $exitAfter Whether to exit immediately after sending the response.
     * @param bool $forTest Whether the response is for testing.
     *
     * @return array
     */
    public static function quickError(string $message, bool $exitAfter = true, bool $forTest = false): array
    {
        return self::error($message)->send($exitAfter, $forTest);
    }

    /**
     * Create a quick info response.
     *
     * @param string $message The info message.
     * @param bool $exitAfter Whether to exit immediately after sending the response.
     * @param bool $forTest Whether the response is for testing.
     *
     * @return array
     */
    public static function quickInfo(string $message, bool $exitAfter = true, bool $forTest = false): array
    {
        return self::info($message)->send($exitAfter, $forTest);
    }

    // -------------------------------------------------------------------------
    // Fluent setters (return $this for chaining)
    // -------------------------------------------------------------------------

    /**
     * Set the response data.
     *
     * @param array $data The data to include in the response.
     *
     * @return self
     */
    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Alias for withData().
     *
     * @param array $data The data to include in the response.
     *
     * @return self
     */
    public function withPayload(array $data): self
    {
        return $this->withData($data);
    }

    /**
     * Set total pages for paginated responses.
     *
     * @param int $totalPages The total number of pages.
     *
     * @return self
     */
    public function withTotalPages(int $totalPages): self
    {
        $this->totalPages = $totalPages;
        return $this;
    }

    /**
     * Set response type (success, error, info).
     *
     * @param string $type The response type.
     *
     * @return self
     */
    public function withType(string $type): self
    {
        if (in_array($type, ['success', 'error', 'info'], true)) {
            $this->type = $type;
        }
        return $this;
    }

    /**
     * Set HTTP status code.
     *
     * @param int $status The HTTP status code.
     *
     * @return self
     */
    public function withHttpStatus(int $status): self
    {
        $this->httpStatus = $status;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Build response array
    // -------------------------------------------------------------------------

    /**
     * Convert the response to array
     *
     * Includes optional data and totalPages if set
     *
     * @return array
     */
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'type' => $this->type,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->totalPages !== null) {
            $response['totalPages'] = $this->totalPages;
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // Send JSON response (CLI-safe & test-friendly)
    // -------------------------------------------------------------------------

    /**
     * Output JSON response, optionally exit, and optionally suppress output for testing
     *
     * @param bool $exitAfter If true, calls exit after sending (ignored in test mode)
     * @param bool $forTest   If true, suppresses echo and returns response array for testing
     * 
     * @return array          The response array (useful for testing)
     */
    public function send(bool $exitAfter = true, bool $forTest = false): array
    {
        $response = $this->toArray();

        // If in test mode, do not echo or exit.
        if ($forTest) {
            return $response;
        }

        // Only set HTTP headers if not running in CLI
        if (php_sapi_name() !== 'cli') {
            http_response_code($this->httpStatus);
            header('Content-Type: application/json');
        }

        echo json_encode($response);

        if ($exitAfter) {
            exit;
        }

        return $response;
    }
}
