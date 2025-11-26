<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Interface FileSystemInterface
 *
 * Defines common file system operations such as writing, deleting,
 * listing files, and ensuring directories exist.
 *
 * @package App\Utils
 */
interface FileSystemInterface
{
    /**
     * Write content to a file.
     *
     * @param string $path    Path to the file.
     * @param string $content Content to be written.
     * @param bool   $append  Whether to append to the file (default: true).
     *
     * @return void
     */
    public function write(string $path, string $content, bool $append = true): void;

    /**
     * Delete a file.
     *
     * @param string $path Path to the file to delete.
     *
     * @return void
     */
    public function delete(string $path): void;

    /**
     * List files matching a pattern (e.g., glob syntax).
     *
     * @param string $pattern The pattern to match files against.
     *
     * @return array<string> List of matching file paths.
     */
    public function listFiles(string $pattern): array;

    /**
     * Ensure the given directory exists.
     *
     * @param string $path Path of the directory to check or create.
     *
     * @return void
     */
    public function ensureDir(string $path): void;
}
