<?php

declare(strict_types=1);

namespace Switon\Core;

use ReflectionClass;
use Switon\Core\Attribute\ResourceAlias;
use ReflectionAttribute;

use function basename;
use function dirname;
use function explode;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function rtrim;
use function str_replace;
use function trim;

/**
 * Registers provider-declared resource aliases.
 *
 * Use when a service provider declares {@see ResourceAlias} attributes and runtime
 * code should resolve package assets from standard package directory structure.
 *
 * @see \Switon\Core\Attribute\ResourceAlias
 * @see \Switon\Core\PathAliasInterface
 */
class ResourceAliasRegistrar implements ResourceAliasRegistrarInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(PathAliasInterface|Lazy $pathAlias, string $providerClass): void
    {
        $reflection = new ReflectionClass($providerClass);
        $attributes = $reflection->getAttributes(ResourceAlias::class, ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes === []) {
            return;
        }

        $file = $reflection->getFileName();
        if (!is_string($file)) {
            return;
        }

        foreach ($attributes as $attribute) {
            /** @var ResourceAlias $resource */
            $resource = $attribute->newInstance();
            $packageResource = $this->detectResource($file, $resource->path, $resource->alias);
            if ($packageResource === null) {
                continue;
            }

            if (!$pathAlias->has($packageResource['alias'])) {
                $pathAlias->set($packageResource['alias'], $packageResource['path']);
            }
        }
    }

    /**
     * @return array{alias: string, path: string}|null
     */
    protected function detectResource(string $file, string $path, ?string $alias): ?array
    {
        $file = str_replace('\\', '/', $file);
        $packageRoot = $this->detectPackageRoot($file);
        if ($packageRoot === null) {
            return null;
        }

        $packageName = $this->detectPackageName($packageRoot);
        if ($packageName === null) {
            return null;
        }

        return [
            'alias' => $alias ?? '@' . $packageName['vendor'] . '.' . $packageName['package'] . '.' . basename(trim($path, '/')),
            'path' => rtrim($packageRoot, '/') . '/' . trim($path, '/'),
        ];
    }

    /**
     * @return array{vendor: string, package: string}|null
     */
    protected function detectPackageName(string $packageRoot): ?array
    {
        $composerJson = file_get_contents($packageRoot . '/composer.json');
        if ($composerJson === false) {
            return null;
        }

        $composer = json_decode($composerJson, true);
        if (!is_array($composer) || !is_string($composer['name'] ?? null) || $composer['name'] === '') {
            return null;
        }

        $parts = explode('/', $composer['name'], 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [
            'vendor' => $parts[0],
            'package' => $parts[1],
        ];
    }

    protected function detectPackageRoot(string $file): ?string
    {
        $directory = dirname($file);

        while ($directory !== '' && $directory !== '.') {
            if (is_file($directory . '/composer.json')) {
                return $directory;
            }

            $parent = dirname($directory);
            if ($parent === $directory) {
                break;
            }

            $directory = $parent;
        }

        return null;
    }
}
