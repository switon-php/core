<?php

declare(strict_types=1);

namespace Switon\Core;

use Switon\Core\Exception\CreateDirectoryFailedException;
use Switon\Core\Exception\FileNotFoundException;
use Switon\Core\Exception\RuntimeException;

/**
 * Filesystem contract with path-alias support.
 *
 * Paths may use aliases such as @app and are resolved before I/O.
 *
 * Road-signs:
 * - resolve aliases before I/O
 * - read/write/glob stay alias-aware
 *
 * @see \Switon\Core\Filesystem
 * @see \Switon\Core\PathAliasInterface
 */
interface FilesystemInterface
{
    /** Check whether a file or directory exists. */
    public function exists(string $path): bool;

    /** Get file size in bytes, or null when unavailable. */
    public function size(string $file): ?int;

    /**
     * Delete one file or wildcard-matched files.
     *
     * @throws RuntimeException
     */
    public function delete(string $path): void;

    /**
     * Read file content.
     *
     * @throws FileNotFoundException
     */
    public function read(string $file): string;

    /**
     * Overwrite file content.
     *
     * @throws RuntimeException
     */
    public function write(string $file, string $data): void;

    /**
     * Append file content.
     *
     * @throws RuntimeException
     */
    public function append(string $file, string $data): void;

    /**
     * Move file or directory.
     *
     * @throws RuntimeException
     */
    public function move(string $src, string $dst, bool $overwrite = false): void;

    /** Check whether path is a directory. */
    public function isDir(string $dir): bool;

    /**
     * Remove directory recursively.
     *
     * @throws RuntimeException
     */
    public function rmdir(string $dir): void;

    /**
     * Create directory recursively.
     *
     * @throws CreateDirectoryFailedException
     */
    public function mkdir(string $dir, int $mode = 0755): void;

    /**
     * Copy file or directory recursively.
     *
     * @throws RuntimeException
     */
    public function copy(string $src, string $dst, bool $overwrite = false): void;

    /**
     * Find paths by glob pattern.
     *
     * @return array<string>
     */
    public function glob(string $pattern, int $flags = 0): array;

    /**
     * List files in directory/pattern.
     *
     * @return list<string>
     */
    public function files(string $dir): array;

    /**
     * List directories in directory/pattern.
     *
     * @return list<string>
     */
    public function directories(string $dir): array;

    /**
     * List direct children names in a directory.
     *
     * @return array<string>
     *
     * @throws RuntimeException
     */
    public function list(string $dir, int $sorting_order = SCANDIR_SORT_ASCENDING): array;

    /** Get file mtime timestamp, or null when unavailable. */
    public function mtime(string $file): ?int;

    /**
     * Change file/directory mode.
     *
     * @throws RuntimeException
     */
    public function chmod(string $file, int $mode): void;
}
