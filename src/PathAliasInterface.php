<?php

declare(strict_types=1);

namespace Switon\Core;

use Switon\Core\Exception\AliasNotFoundException;
use Switon\Core\Exception\CircularAliasException;
use Switon\Core\Exception\InvalidAliasNameException;

/**
 * Path-alias contract.
 *
 * Aliases are named with an `@` prefix (for example `@app`) and resolve to normalized file-system paths.
 *
 * Guidance: Register stable roots here; higher-level code should consume resolved paths instead of rebuilding path logic.
 *
 * Road-signs:
 * - `set()` registers aliases and nested alias paths
 * - `resolve()` expands aliases and placeholder context
 * - `FilesystemInterface` consumes resolved paths
 * - renderer and asset helpers read aliases directly
 *
 * @see \Switon\Core\PathAlias
 * @see \Switon\Core\PathAliasInterface::resolve()
 * @see \Switon\Core\FilesystemInterface
 * @see \Switon\Rendering\Renderer
 * @see \Switon\Viewing\Asset
 */
interface PathAliasInterface
{
    /**
     * Return all registered aliases.
     *
     * @return array<string, string>
     */
    public function all(): array;

    /**
     * Register or update one alias.
     *
     * Resolves and stores the path for <code>$name</code> only; aliases that already
     * referenced <code>$name</code> are not updated. After changing a base alias
     * (for example <code>@root</code>), call <code>set()</code> again on dependents
     * (for example <code>@vendor</code>) or register roots before derived aliases.
     *
     * @param string $name Alias name (must start with <code>@</code>)
     * @param string $path Raw path or alias-based path
     *
     * @return string Resolved stored path
     *
     * @throws InvalidAliasNameException
     * @throws AliasNotFoundException
     * @throws CircularAliasException
     */
    public function set(string $name, string $path): string;

    /**
     * Get resolved path for one alias.
     *
     * @param string $name Alias name
     *
     * @return string|null Null when alias is missing
     *
     * @throws InvalidAliasNameException
     */
    public function get(string $name): ?string;

    /**
     * Check whether alias exists.
     *
     * @param string $name Alias name
     *
     * @throws InvalidAliasNameException
     */
    public function has(string $name): bool;

    /**
     * Resolve a path string (alias path or plain path).
     *
     * Supports placeholder replacement with <code>{key}</code> from context.
     *
     * @param string $path
     * @param array<string, mixed> $context
     *
     * @throws AliasNotFoundException
     */
    public function resolve(string $path, array $context = []): string;

    /**
     * Remove one alias.
     *
     * @param string $name Alias name
     *
     * @throws InvalidAliasNameException
     */
    public function remove(string $name): void;
}
