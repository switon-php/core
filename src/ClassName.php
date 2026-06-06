<?php

declare(strict_types=1);

namespace Switon\Core;

use function basename;
use function explode;
use function implode;
use function is_string;
use function ltrim;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strrpos;
use function substr;
use function trim;

/**
 * Class-name string helpers.
 *
 * Works on raw strings and does not require classes to be loadable.
 *
 * @see \Switon\Core\Naming
 * @see \Switon\Core\Strings
 */
class ClassName
{
    /**
     * Normalize user-supplied FQCN input (CLI args often contain escaped backslashes).
     *
     * This is string-only: it does not require the class to be loadable.
     */
    public static function normalize(string $fqcn): string
    {
        $fqcn = trim($fqcn);
        if ($fqcn === '') {
            return $fqcn;
        }
        while (str_contains($fqcn, '\\\\')) {
            $fqcn = str_replace('\\\\', '\\', $fqcn);
        }
        return trim($fqcn, '\\');
    }

    /** Whether a class-like string follows the <code>XxxInterface</code> naming convention. */
    public static function isInterface(string $fullClassName): bool
    {
        return str_ends_with($fullClassName, 'Interface');
    }

    /**
     * Whether container binding (`id → definition`) matches the built-in auto-mapping convention
     * (`XxxInterface → Xxx`).
     *
     * Works on raw strings and does not require the classes to be loadable.
     */
    public static function isAutoMapPair(string $id, mixed $definition): bool
    {
        if (!is_string($definition)) {
            return false;
        }

        return self::isInterface($id) && ($definition . 'Interface') === $id;
    }

    /** Return namespace part of a class name. */
    public static function namespace(string $fullClassName): string
    {
        $pos = strrpos($fullClassName, '\\');
        if ($pos === false) {
            return '';
        }
        return substr($fullClassName, 0, $pos);
    }

    /** Return short class name without namespace. */
    public static function short(string $fullClassName): string
    {
        $pos = strrpos($fullClassName, '\\');
        if ($pos === false) {
            return $fullClassName;
        }
        return substr($fullClassName, $pos + 1);
    }

    /**
     * Split class name into namespace and short class name.
     *
     * @return array{namespace: string, className: string}
     */
    public static function split(string $fullClassName): array
    {
        $pos = strrpos($fullClassName, '\\');
        if ($pos === false) {
            return [
                'namespace' => '',
                'className' => $fullClassName,
            ];
        }
        return [
            'namespace' => substr($fullClassName, 0, $pos),
            'className' => substr($fullClassName, $pos + 1),
        ];
    }

    /** Join namespace and short class name. */
    public static function join(string $namespace, string $className): string
    {
        if ($namespace === '') {
            return $className;
        }
        // Remove trailing backslash if present
        $namespace = rtrim($namespace, '\\');
        return $namespace . '\\' . $className;
    }

    /**
     * Convert class-like string to a stable dot identifier.
     *
     * Default mode keeps more semantic layers and de-duplicates singular/plural repeats.
     * Compact mode drops the root namespace plus leading `Areas`, then builds the shorter controller-style dot id.
     *
     * @param array<string> $excludes Exact substrings to remove before conversion
     */
    public static function dotId(string $className, array $excludes = [], bool $compact = false): string
    {
        $cleanName = $className;
        if ($excludes !== []) {
            $cleanName = str_replace($excludes, '\\', $className);
        }

        if ($compact) {
            $parts = explode('\\', ltrim($cleanName, '\\'));
            if (count($parts) >= 2) {
                array_shift($parts);
            }
            if (isset($parts[0]) && $parts[0] === 'Areas') {
                array_shift($parts);
            }

            $short = array_pop($parts);
            $layer = array_pop($parts);
            if ($short === null) {
                return '';
            }

            return implode('.', array_map(
                static fn (string $s): string => Naming::kebab($s),
                [...$parts, basename($short, $layer ?? '')]
            ));
        }

        $parts = explode('\\', $cleanName);
        $parts = array_map(static fn (string $part) => str_replace('_', '.', Naming::snake($part)), $parts);
        $words = explode('.', implode('.', $parts));

        $seen = [];
        foreach ($words as $word) {
            if ($word === '' || isset($seen[$word])) {
                continue;
            }

            // Check if word is singular/plural variant of already-seen word
            if (!self::isSingularPluralMatch($word, $seen)) {
                $seen[$word] = true;
            }
        }

        return implode('.', array_keys($seen));
    }

    /**
     * Check whether word is a singular/plural variant of seen words.
     *
     * @param array<string, true> $seen
     */
    protected static function isSingularPluralMatch(string $word, array $seen): bool
    {
        // Skip short words - pluralization unreliable for 1-2 char words
        if (strlen($word) < 3) {
            return false;
        }

        $wordSingular = Naming::singular($word);
        $wordPlural = Naming::plural($word);

        foreach ($seen as $other => $_) {
            $otherSingular = Naming::singular($other);
            $otherPlural = Naming::plural($other);

            // Check if word and other are singular/plural variants
            if ($wordPlural === $other
                || $word === $otherPlural
                || ($wordSingular === $other && $wordSingular !== $word)
                || ($otherSingular === $word && $otherSingular !== $other)) {
                return true;
            }
        }

        return false;
    }
}
