<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Helper;

use App\Utils\CookieStorageInterface;

/**
 * Class TestCookieStorage
 *
 * An in-memory implementation of CookieStorageInterface for testing purposes.
 *
 * Provides basic cookie storage operations without relying on real HTTP cookies,
 * enabling predictable behavior in integration and unit tests.
 *
 * @package Tests\Integration\Api\Helper
 */
class TestCookieStorage implements CookieStorageInterface
{
    /**
     * @var array<string, string> Simulated cookie store
     */
    private array $cookies = [];

    /**
     * Retrieve a cookie value by name.
     *
     * @param string $name
     * 
     * @return string|null Returns the cookie value or null if not set.
     */
    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Set a cookie value (in-memory only).
     *
     * @param string $name
     * @param string $value
     * @param int $expires Expiry timestamp (ignored in test storage)
     * 
     * @return void
     */
    public function set(string $name, string $value, int $expires): void
    {
        $this->cookies[$name] = $value;
    }

    /**
     * Delete a cookie by name.
     *
     * Simulates cookie expiry by removing from internal storage.
     *
     * @param string $name
     * 
     * @return void
     */
    public function delete(string $name): void
    {
        unset($this->cookies[$name]);
    }

    /**
     * Return all cookies for test verification.
     *
     * @return array<string, string> Current stored cookies
     */
    public function all(): array
    {
        return $this->cookies;
    }
}
