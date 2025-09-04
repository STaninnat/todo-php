<?php

namespace App\Utils;

/**
 * Interface for handling cookie storage operations.
 */
interface CookieStorageInterface
{
    /**
     * Retrieve the value of a cookie by its name.
     *
     * @param string $name The name of the cookie.
     * @return string|null The value of the cookie if it exists, otherwise null.
     */
    public function get(string $name): ?string;
}
