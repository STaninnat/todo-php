<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Native implementation of CookieStorageInterface.
 * 
 * This class uses PHP's native $_COOKIE superglobal to retrieve cookie values.
 */
class NativeCookieStorage implements CookieStorageInterface
{
    /**
     * Retrieve the value of a cookie by its name using PHP's $_COOKIE.
     *
     * @param string $name The name of the cookie.
     * @return string|null The value of the cookie if it exists, otherwise null.
     */
    public function get(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }
}
