<?php

namespace Tests\Unit\Utils\Fakes;

use App\Utils\FileSystemInterface;

/**
 * Class FakeFileSystem
 *
 * A fake file system implementation for unit testing.
 * Stores files and directories in memory without touching the real filesystem.
 *
 * @package Tests\Unit\Utils\Fakes
 */
class FakeFileSystem implements FileSystemInterface
{
    /** @var array<string, string> In-memory file storage (path => content) */
    private array $files = [];

    /** @var array<string, bool> In-memory directory storage */
    private array $dirs = [];

    /**
     * Check if a directory exists in memory.
     *
     * @param string $path Directory path
     *
     * @return bool True if directory exists, false otherwise
     */
    public function hasDir(string $path): bool
    {
        return isset($this->dirs[$path]);
    }

    /**
     * Write content to a file in memory.
     *
     * @param string $path    File path
     * @param string $content Content to write
     * @param bool   $append  Whether to append to existing content (default: true)
     *
     * @return void
     */
    public function write(string $path, string $content, bool $append = true): void
    {
        if ($append && isset($this->files[$path])) {
            // Append content if file exists
            $this->files[$path] .= $content;
        } else {
            // Overwrite or create new file
            $this->files[$path] = $content;
        }
    }

    /**
     * Delete a file from memory.
     *
     * @param string $path File path
     *
     * @return void
     */
    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }

    /**
     * List files matching a pattern.
     *
     * Supports '*' and '?' wildcards similar to glob().
     *
     * @param string $pattern Pattern to match
     *
     * @return array<string> Matching file paths
     */
    public function listFiles(string $pattern): array
    {
        $regex = '#^' . str_replace(['*', '?'], ['.*', '.'], $pattern) . '$#';

        // Filter file paths using regex
        return array_values(array_filter(
            array_keys($this->files),
            fn(string $f): bool => (bool) preg_match($regex, $f)
        ));
    }

    /**
     * Ensure a directory exists in memory.
     *
     * @param string $path Directory path
     *
     * @return void
     */
    public function ensureDir(string $path): void
    {
        $this->dirs[$path] = true;
    }

    /**
     * Get the content of a file.
     *
     * @param string $path File path
     *
     * @return string|null File content or null if file does not exist
     */
    public function getFileContent(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    /**
     * Check if a file exists.
     *
     * @param string $path File path
     *
     * @return bool True if file exists, false otherwise
     */
    public function hasFile(string $path): bool
    {
        return isset($this->files[$path]);
    }
}
