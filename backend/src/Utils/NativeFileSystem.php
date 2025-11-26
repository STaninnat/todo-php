<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Class NativeFileSystem
 *
 * Native implementation of FileSystemInterface using PHP built-in functions.
 * Provides file operations such as writing, deleting, listing, and ensuring directories.
 *
 * @package App\Utils
 */
class NativeFileSystem implements FileSystemInterface
{
    /**
     * Write content to a file.
     *
     * @param string $path    Path to the file.
     * @param string $content Content to write.
     * @param bool   $append  Whether to append to the file (default: true).
     *
     * @return void
     */
    public function write(string $path, string $content, bool $append = true): void
    {
        // Write content to file (append if specified)
        file_put_contents($path, $content, $append ? FILE_APPEND : 0);
    }

    /**
     * Delete a file.
     *
     * @param string $path Path to the file.
     *
     * @return void
     */
    public function delete(string $path): void
    {
        // Suppress error if file does not exist or cannot be deleted
        @unlink($path);
    }

    /**
     * List files matching a pattern (glob syntax).
     *
     * @param string $pattern Pattern for matching files.
     *
     * @return array<string> List of matching file paths.
     */
    public function listFiles(string $pattern): array
    {
        // Use glob() to find files; return empty array if no match
        return glob($pattern) ?: [];
    }

    /**
     * Ensure the directory exists; create it if not.
     *
     * @param string $path Path of the directory.
     *
     * @return void
     */
    public function ensureDir(string $path): void
    {
        // Create directory recursively if it does not exist
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
