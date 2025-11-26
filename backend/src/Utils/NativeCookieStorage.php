<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Class NativeCookieStorage
 * 
 * Native implementation of CookieStorageInterface.
 * 
 * This class uses PHP's native $_COOKIE superglobal to retrieve cookie values.
 * 
 * @package App\Utils
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
        $val = $_COOKIE[$name] ?? null;

        if ($val === null) {
            return null;
        }

        if (!is_string($val)) {
            return null;
        }

        return $val;
    }

    public function set(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public function delete(string $name): void
    {
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        unset($_COOKIE[$name]);
    }
}
