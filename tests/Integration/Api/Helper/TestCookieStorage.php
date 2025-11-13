<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Helper;

use App\Utils\CookieStorageInterface;

/**
 * Class TestCookieStorage
 * 
 * A lightweight in-memory implementation of CookieStorageInterface
 * for integration and unit tests.
 * 
 */
class TestCookieStorage implements CookieStorageInterface
{
    /**
     * @var array<string, string> Simulated cookie store
     */
    private array $cookies = [];

    /**
     * Get cookie value by name
     */
    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Set cookie (in-memory only)
     */
    public function set(string $name, string $value, int $expires): void
    {
        $this->cookies[$name] = $value;
    }

    /**
     * Delete cookie (simulate expiry)
     */
    public function delete(string $name): void
    {
        unset($this->cookies[$name]);
    }

    /**
     * For test verification — returns all cookies
     * 
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->cookies;
    }
}
