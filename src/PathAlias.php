<?php

declare(strict_types=1);

namespace Switon\Core;

use JsonSerializable;
use Switon\Core\Attribute\Autowired;
use Switon\Core\Exception\AliasNotFoundException;
use Switon\Core\Exception\CircularAliasException;
use Switon\Core\Exception\InvalidAliasNameException;
use Switon\Core\Exception\ProjectRootDetectionException;

use function implode;
use function in_array;
use function rtrim;
use function str_starts_with;
use function strpos;
use function strtr;
use function substr;

/**
 * Resolves path aliases to concrete filesystem paths.
 *
 * Guidance: Register base aliases before dependents; <code>set()</code> does not cascade
 * updates to aliases that were already resolved from an earlier value.
 *
 * @see \Switon\Core\PathAliasInterface
 * @see \Switon\Core\Runtime::getRoot()
 */
class PathAlias implements PathAliasInterface, JsonSerializable
{
    /** @var array<string, string> Alias map (`@name` => absolute path). */
    #[Autowired] protected array $aliases = [];

    /**
     * Initialize built-in aliases when not provided by configuration.
     *
     * @throws ProjectRootDetectionException
     * @throws CircularAliasException
     */
    public function __construct()
    {
        if (!isset($this->aliases['@root'])) {
            $this->aliases['@root'] = Runtime::getRoot();
        }

        $root = $this->aliases['@root'];
        $this->aliases += [
            '@vendor' => "$root/vendor",
            '@runtime' => "$root/runtime",
            '@app' => "$root/app",
            '@resources' => "$root/resources",
        ];

        foreach ($this->aliases as $name => $value) {
            if (str_starts_with($value, '@')) {
                $this->aliases[$name] = $this->resolveRecursive($value, [$name]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->aliases;
    }

    /** @throws InvalidAliasNameException */
    protected function validate(string $name): void
    {
        if (!str_starts_with($name, '@')) {
            InvalidAliasNameException::raise('Alias name "{name}" must start with "@".', ['name' => $name]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $name, string $path): string
    {
        $this->validate($name);

        if ($path === '') {
            return $this->aliases[$name] = $path;
        } elseif (!str_starts_with($path, '@')) {
            return $this->aliases[$name] = rtrim(strtr($path, '\\', '/'), '/');
        } else {
            return $this->aliases[$name] = $this->resolveRecursive($path, [$name]);
        }
    }

    /**
     * Resolve nested alias references and detect circular dependencies.
     *
     * @param array<string> $chain Current resolution chain for cycle detection
     *
     * @throws CircularAliasException
     * @throws AliasNotFoundException
     */
    protected function resolveRecursive(string $path, array $chain): string
    {
        // Extract alias name from path (e.g., "@app/config" -> "@app")
        $pos = strpos($path, '/');
        $aliasName = $pos === false ? $path : substr($path, 0, $pos);

        // Check for circular reference by scanning the resolution chain
        // If we've seen this alias before in the current resolution path, we have a cycle
        if (in_array($aliasName, $chain, true)) {
            $chain[] = $aliasName;  // Add current alias to show complete cycle
            CircularAliasException::raise('Circular alias dependency detected: {chain}', ['chain' => implode(' -> ', $chain)]);
        }

        // Verify the referenced alias exists in our registry
        if (!isset($this->aliases[$aliasName])) {
            AliasNotFoundException::raise('Alias "{alias}" does not exist for path "{path}".', ['alias' => $aliasName, 'path' => $path]);
        }

        // Get the value this alias points to
        $referencedValue = $this->aliases[$aliasName];

        // If the referenced value is itself an alias (starts with @), recurse deeper
        // Add current alias to chain to detect cycles in deeper levels
        if (str_starts_with($referencedValue, '@')) {
            $chain[] = $aliasName;  // Extend resolution chain
            $referencedValue = $this->resolveRecursive($referencedValue, $chain);
        }

        // Build final resolved path
        if ($pos === false) {
            // Simple alias reference (e.g., "@app" -> resolved value)
            return $referencedValue;
        } else {
            // Path with subdirectory (e.g., "@app/config" -> resolved_value + "/config")
            return $referencedValue . substr($path, $pos);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): ?string
    {
        $this->validate($name);

        return $this->aliases[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        $this->validate($name);

        return isset($this->aliases[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $path, array $context = []): string
    {
        if ($context !== []) {
            $replacements = [];
            foreach ($context as $k => $v) {
                $replacements["{{$k}}"] = (string)$v;
            }

            $path = strtr($path, $replacements);
        }

        if (!str_starts_with($path, '@')) {
            return $path;
        }

        if (($pos = strpos($path, '/')) === false) {
            if (!isset($this->aliases[$path])) {
                AliasNotFoundException::raise('Alias "{path}" does not exist.', ['path' => $path]);
            }
            return $this->aliases[$path];
        }

        $alias = substr($path, 0, $pos);

        if (!isset($this->aliases[$alias])) {
            AliasNotFoundException::raise('Alias "{alias}" does not exist for path "{path}".', ['alias' => $alias, 'path' => $path]);
        }

        return $this->aliases[$alias] . substr($path, $pos);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name): void
    {
        $this->validate($name);

        unset($this->aliases[$name]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
