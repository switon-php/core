<?php

declare(strict_types=1);

namespace Switon\Core;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Exception\AliasNotFoundException;
use Switon\Core\Exception\CreateDirectoryFailedException;
use Switon\Core\Exception\FileNotFoundException;
use Switon\Core\Exception\RuntimeException;

use function basename;
use function chmod;
use function closedir;
use function copy;
use function dirname;
use function error_get_last;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function filesize;
use function fnmatch;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function opendir;
use function readdir;
use function rename;
use function rmdir;
use function rtrim;
use function scandir;
use function str_contains;
use function str_starts_with;
use function strtr;
use function unlink;

/**
 * Filesystem implementation with path-alias resolution.
 *
 * @see \Switon\Core\FilesystemInterface
 * @see \Switon\Core\PathAliasInterface
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\Exception\FileNotFoundException
 * @see \Switon\Core\Exception\CreateDirectoryFailedException
 */
class Filesystem implements FilesystemInterface
{
    #[Autowired] protected PathAliasInterface $pathAlias;

    /**
     * Check if file or directory exists.
     *
     * @param string $path File or directory path (supports aliases)
     *
     * @return bool True if exists, false otherwise
     */
    public function exists(string $path): bool
    {
        $resolved = $this->pathAlias->resolve($path);
        return is_file($resolved) || is_dir($resolved);
    }

    /**
     * Get file size in bytes.
     *
     * @param string $file File path (supports aliases)
     *
     * @return int|null File size in bytes, null if file doesn't exist or error
     */
    public function size(string $file): ?int
    {
        $v = @filesize($this->pathAlias->resolve($file));

        return $v === false ? null : $v;
    }

    /**
     * Delete file or files matching pattern.
     *
     * Supports wildcard patterns for batch deletion.
     *
     * @param string $path File path or pattern (supports aliases and wildcards)
     *
     * @throws RuntimeException If deletion fails
     *
     * @codeCoverageIgnore Error branches for file deletion failures are difficult to test
     *                     as they require file system permission errors that are hard to
     *                     simulate in unit tests.
     */
    public function delete(string $path): void
    {
        $resolved = $this->pathAlias->resolve($path);
        if (str_contains($resolved, '*')) {
            foreach ($this->files($resolved) as $f) {
                if (!unlink($f) && $this->exists($f)) {
                    $error = error_get_last()['message'] ?? '';
                    RuntimeException::raise('Failed to delete file "{f}": "{error}".', ['f' => $f, 'error' => $error]);
                }
            }
        } else {
            $file = $resolved;

            if (!unlink($file) && $this->exists($file)) {
                $error = error_get_last()['message'] ?? '';
                RuntimeException::raise('Failed to delete file "{file}": "{error}".', ['file' => $file, 'error' => $error]);
            }
        }
    }

    /** @throws CreateDirectoryFailedException */
    protected function mkdirInternal(string $dir, int $mode = 0755): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            CreateDirectoryFailedException::raise('Failed to create directory "{dir}" with mode {mode}', ['dir' => $dir, 'mode' => decoct($mode)]);
        }
    }

    /**
     * Read file contents.
     *
     * @param string $file File path (supports aliases)
     *
     * @return string File contents
     *
     * @throws FileNotFoundException If file not found or unreadable
     */
    public function read(string $file): string
    {
        if (($r = @file_get_contents($this->pathAlias->resolve($file))) === false) {
            FileNotFoundException::raise('File "{file}" not found or unreadable.', ['file' => $file]);
        }

        return $r;
    }

    /** @throws RuntimeException */
    public function write(string $file, string $data): void
    {
        $file = $this->pathAlias->resolve($file);

        $this->mkdirInternal(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to write to file "{file}": "{error}".', ['file' => $file, 'error' => $error]);
        }
    }

    /**
     * Append data to file.
     *
     * Creates parent directories automatically. Uses file locking for safe concurrent writes.
     *
     * @param string $file File path (supports aliases)
     * @param string $data Data to append
     *
     * @throws RuntimeException If append operation fails
     */
    public function append(string $file, string $data): void
    {
        $file = $this->pathAlias->resolve($file);
        $this->mkdirInternal(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to write to file "{file}": "{error}".', ['file' => $file, 'error' => $error]);
        }
    }

    /**
     * Move file or directory to new location.
     *
     * Creates parent directories automatically. If destination ends with slash,
     * source basename is appended to destination path.
     *
     * @param string $src Source path (supports aliases)
     * @param string $dst Destination path (supports aliases)
     * @param bool $overwrite Whether to overwrite existing destination
     *
     * @throws RuntimeException If move operation fails or destination exists without overwrite
     */
    public function move(string $src, string $dst, bool $overwrite = false): void
    {
        $src = $this->pathAlias->resolve($src);
        $dst = $this->pathAlias->resolve($dst);

        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if (!$overwrite && is_file($dst)) {
            RuntimeException::raise('Cannot move "{src}" to "{dst}": destination file already exists', ['src' => $src, 'dst' => $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            $this->mkdirInternal($dir);
        }

        if (!rename($src, $dst)) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to move "{src}" to "{dst}": "{error}".', ['src' => $src, 'dst' => $dst, 'error' => $error]);
        }
    }

    /**
     * Check if path is directory.
     *
     * @param string $dir Directory path (supports aliases)
     *
     * @return bool True if path is a directory, false otherwise
     */
    public function isDir(string $dir): bool
    {
        return is_dir($this->pathAlias->resolve($dir));
    }

    /**
     * Internal method to recursively remove directory contents.
     *
     * Recursively deletes all children and then removes the directory itself.
     *
     * @param string $dir Directory path to remove
     *
     * @throws RuntimeException If removal fails
     *
     * @codeCoverageIgnore Error branches for file/directory deletion failures are difficult
     *                     to test as they require file system permission errors that are
     *                     hard to simulate in unit tests.
     */
    protected function rmdirInternal(string $dir): void
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rmdirInternal($path);
            } elseif (!unlink($path)) {
                // Handles regular files, symlinks, and other special files
                $error = error_get_last()['message'] ?? '';
                RuntimeException::raise('Failed to delete "{path}": "{error}".', ['path' => $path, 'error' => $error]);
            }
        }

        if (!rmdir($dir)) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to delete directory "{dir}": "{error}".', ['dir' => $dir, 'error' => $error]);
        }
    }

    /**
     * Remove directory and all its contents recursively.
     *
     * Silently returns if directory doesn't exist.
     *
     * @param string $dir Directory path (supports aliases)
     *
     * @throws RuntimeException If removal fails
     */
    public function rmdir(string $dir): void
    {
        $dir = $this->pathAlias->resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        $this->rmdirInternal($dir);
    }

    /**
     * Create directory with specified permissions.
     *
     * Creates parent directories automatically if they don't exist.
     *
     * @param string $dir Directory path (supports aliases)
     * @param int $mode Directory permissions (default: 0755)
     *
     * @throws CreateDirectoryFailedException If directory creation fails
     */
    public function mkdir(string $dir, int $mode = 0755): void
    {
        $this->mkdirInternal($this->pathAlias->resolve($dir), $mode);
    }

    /**
     * Internal method to recursively copy directory contents.
     *
     * Recursively copies files/subdirectories and creates destination directories as needed.
     *
     * @param string $src Source directory path
     * @param string $dst Destination directory path
     * @param bool $overwrite Whether to overwrite existing files
     *
     * @throws RuntimeException If copy operation fails
     *
     * @codeCoverageIgnore Error branch for file copy failure is difficult to test as it
     *                     requires file system errors (e.g., disk full, permission denied)
     *                     that are hard to simulate in unit tests.
     */
    protected function copyInternal(string $src, string $dst, bool $overwrite): void
    {
        foreach (scandir($src, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_dir($srcPath)) {
                // Always recurse into directories - let file-level overwrite logic handle conflicts
                $this->mkdirInternal($dstPath);
                $this->copyInternal($srcPath, $dstPath, $overwrite);
            } elseif (($overwrite || !file_exists($dstPath)) && !copy($srcPath, $dstPath)) {
                // Handles regular files, symlinks, and other special files
                $error = error_get_last()['message'] ?? '';
                RuntimeException::raise(
                    'Copy "{srcPath}" to "{dstPath}" failed: {error}',
                    ['srcPath' => $srcPath, 'dstPath' => $dstPath, 'error' => $error]
                );
            }
        }
    }

    /**
     * Copy file or directory to new location.
     *
     * For files: copies single file to destination.
     * For directories: recursively copies entire directory tree.
     * Creates parent directories automatically.
     *
     * @param string $src Source path (supports aliases)
     * @param string $dst Destination path (supports aliases)
     * @param bool $overwrite Whether to overwrite existing files
     *
     * @throws RuntimeException If copy operation fails or source doesn't exist
     */
    public function copy(string $src, string $dst, bool $overwrite = false): void
    {
        $src = $this->pathAlias->resolve($src);
        $dst = $this->pathAlias->resolve($dst);

        if (is_file($src)) {
            if (!$overwrite && file_exists($dst)) {
                RuntimeException::raise('Cannot copy "{src}" to "{dst}": destination file already exists', ['src' => $src, 'dst' => $dst]);
            }
            $this->mkdirInternal(dirname($dst));
            if (!copy($src, $dst)) {
                $error = error_get_last()['message'] ?? '';
                RuntimeException::raise('Failed to copy "{src}" to "{dst}": "{error}".', ['src' => $src, 'dst' => $dst, 'error' => $error]);
            }
        } elseif (is_dir($src)) {
            $this->mkdirInternal($dst);
            $this->copyInternal($src, $dst, $overwrite);
        } else {
            RuntimeException::raise('Cannot copy "{src}" to "{dst}": source file not found', ['src' => $src, 'dst' => $dst]);
        }
    }

    /**
     * Find files matching glob pattern.
     *
     * Supports standard glob patterns with wildcards. Handles phar:// URLs specially.
     * Normalizes path separators to forward slashes on Windows.
     *
     * @param string $pattern Glob pattern (supports aliases and wildcards)
     * @param int $flags Glob flags (e.g., GLOB_ONLYDIR)
     *
     * @return list<string> Array of matching file paths
     *
     * @codeCoverageIgnore phar:// path handling requires phar environment which is not
     *                     available in standard unit test environment.
     */
    public function glob(string $pattern, int $flags = 0): array
    {
        $pattern = $this->pathAlias->resolve($pattern);

        if (str_starts_with($pattern, 'phar://')) {
            $dir = dirname($pattern);

            if (!is_dir($dir)) {
                return [];
            }

            $r = [];
            $p = basename($pattern);
            $h = @opendir($dir);
            if ($h === false) {
                return [];
            }

            try {
                while (($file = readdir($h)) !== false) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (!fnmatch($p, $file)) {
                        continue;
                    }

                    if (($flags & GLOB_ONLYDIR) && !is_dir($dir . '/' . $file)) {
                        continue;
                    }

                    $r[] = $dir . '/' . $file;
                }
            } finally {
                closedir($h);
            }
        } else {
            $r = glob($pattern, $flags);
            $r = $r !== false ? $r : [];
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($r as $k => $v) {
                $r[$k] = strtr($v, '\\', '/');
            }
        }

        return $r;
    }

    /**
     * Get all files in directory or matching pattern.
     *
     * Returns only files, not directories. Supports wildcard patterns.
     *
     * @param string $dir Directory path or pattern (supports aliases)
     *
     * @return list<string> Array of file paths
     */
    public function files(string $dir): array
    {
        $dir = $this->pathAlias->resolve($dir);

        $files = [];
        foreach ($this->glob($dir . (str_contains($dir, '*') ? '' : '/*')) as $item) {
            if (is_file($item)) {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * Get all directories in directory or matching pattern.
     *
     * Returns only directories, not files. Supports wildcard patterns.
     *
     * @param string $dir Directory path or pattern (supports aliases)
     *
     * @return list<string> Array of directory paths
     */
    public function directories(string $dir): array
    {
        $dir = $this->pathAlias->resolve($dir);
        if (DIRECTORY_SEPARATOR === '\\') {
            $dir = strtr($dir, '\\', '/');
        }

        return $this->glob($dir . (str_contains($dir, '*') ? '' : '/*'), GLOB_ONLYDIR);
    }

    /**
     * List directory contents.
     *
     * Returns both files and directories, excluding "." and "..".
     *
     * @param string $dir Directory path (supports aliases)
     * @param int $sorting_order
     *
     * @return array<string> Array of item names (not full paths)
     *
     * @throws RuntimeException If listing fails
     * @throws AliasNotFoundException If alias resolution fails
     */
    public function list(string $dir, int $sorting_order = SCANDIR_SORT_ASCENDING): array
    {
        $r = @scandir($this->pathAlias->resolve($dir), $sorting_order);
        if ($r === false) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to list directory "{dir}": "{error}".', ['dir' => $dir, 'error' => $error]);
        }

        $items = [];
        foreach ($r as $item) {
            if ($item !== '.' && $item !== '..') {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Get file last modification time.
     *
     * @param string $file File path (supports aliases)
     *
     * @return int|null Unix timestamp of last modification, null if file doesn't exist or error
     */
    public function mtime(string $file): ?int
    {
        $v = @filemtime($this->pathAlias->resolve($file));

        return $v === false ? null : $v;
    }

    /**
     * Change file or directory permissions.
     *
     * @param string $file File or directory path (supports aliases)
     * @param int $mode New permissions mode (e.g., 0755, 0644)
     *
     * @throws RuntimeException If chmod operation fails
     */
    public function chmod(string $file, int $mode): void
    {
        if (!@chmod($this->pathAlias->resolve($file), $mode)) {
            $error = error_get_last()['message'] ?? '';
            RuntimeException::raise('Failed to chmod "{file}" to mode "{mode}": "{error}".', ['file' => $file, 'mode' => $mode, 'error' => $error]);
        }
    }
}
