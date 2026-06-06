<?php

declare(strict_types=1);

namespace Switon\Core;

use ReflectionClass;
use Switon\Core\Attribute\Autowired;
use Generator;
use Throwable;

use function array_shift;
use function class_exists;
use function count;
use function is_int;
use function preg_match;
use function preg_quote;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;
use function substr_count;

/**
 * Turns scan configuration into class names; optional filter by class-level PHP attribute.
 *
 * Road-signs:
 * - direct class names
 * - glob => class pattern
 * - optional base type + class attribute filter
 *
 * @see \Switon\Core\ClassScannerInterface
 * @see \Switon\Core\FilesystemInterface
 * @see \Switon\Core\PathAliasInterface
 */
class ClassScanner implements ClassScannerInterface
{
    #[Autowired] protected FilesystemInterface $filesystem;
    #[Autowired] protected PathAliasInterface $pathAlias;

    /**
     * {@inheritDoc}
     *
     * Skips missing classes and reflection failures; glob wildcards match one path
     * segment of word characters (<code>[A-Za-z0-9_]</code>) per <code>*</code>.
     */
    public function scan(array $entries, ?string $attributeClass = null, ?string $baseType = null): array
    {
        /** @var list<class-string> $out */
        $out = [];
        foreach ($this->iterateEntryClassSources($entries) as $source) {
            $className = $source['class'];
            $filePath = $source['path'];

            try {
                if (!class_exists($className)) {
                    continue;
                }
                $rClass = new ReflectionClass($className);
            } catch (Throwable $e) {
                continue;
            }

            if ($baseType !== null && !is_a($className, $baseType, true)) {
                continue;
            }

            if ($attributeClass !== null && $rClass->getAttributes($attributeClass) === []) {
                continue;
            }

            $out[] = $className;
        }

        return $out;
    }

    /**
     * @param array<int|string, string> $entries
     *
     * @return Generator<array{class: string, path: string}>
     */
    protected function iterateEntryClassSources(array $entries): Generator
    {
        foreach ($entries as $key => $value) {
            if (is_int($key)) {
                yield ['path' => '', 'class' => $value];
                continue;
            }

            foreach ($this->filesystem->glob($key) as $file) {
                if (DIRECTORY_SEPARATOR === '\\') {
                    $file = str_replace('\\', '/', $file);
                }
                $className = $this->matchFileToClassPattern($file, $key, $value);
                if ($className !== null) {
                    yield ['path' => $file, 'class' => $className];
                }
            }
        }
    }

    protected function matchFileToClassPattern(string $file, string $glob, string $classPattern): ?string
    {
        $candidatePath = $file;

        // Alias globs match against resolved absolute paths; plain globs match
        // against the app-relative suffix for historical compatibility.
        if (str_starts_with($glob, '@')) {
            $glob = $this->pathAlias->resolve($glob);
        } else {
            $root = $this->pathAlias->resolve('@app') . '/';
            if (str_starts_with($candidatePath, $root)) {
                $candidatePath = substr($candidatePath, strlen($root));
            }
        }

        // Escape literal path text first, then turn '*' into a single path-token capture.
        $regex = '#^' . str_replace('\*', '(\w+)', preg_quote($glob, '#')) . '$#';

        if (preg_match($regex, $candidatePath, $matches) !== 1) {
            return null;
        }

        array_shift($matches);
        $placeholderCount = substr_count($classPattern, '*');
        if (count($matches) !== $placeholderCount) {
            return null;
        }

        $resolvedClass = $classPattern;
        foreach ($matches as $placeholder) {
            $pos = strpos($resolvedClass, '*');
            if ($pos === false) {
                return null;
            }

            $resolvedClass = substr($resolvedClass, 0, $pos) . $placeholder . substr($resolvedClass, $pos + 1);
        }

        return $resolvedClass;
    }
}
