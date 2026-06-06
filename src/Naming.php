<?php

declare(strict_types=1);

namespace Switon\Core;

use function ctype_upper;
use function in_array;
use function str_ends_with;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function ucfirst;

/**
 * Naming-convention conversion helpers.
 *
 * @see \Switon\Core\Strings
 * @see \Switon\Core\ClassName
 */
class Naming
{
    /** Convert value to camelCase. */
    public static function camel(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return lcfirst(str_replace(' ', '', $value));
    }

    /** Convert value to snake_case. */
    public static function snake(string $value): string
    {
        // Insert underscore before uppercase letters that follow lowercase or digits
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value);
        // Insert underscore before uppercase letters that are followed by lowercase (for consecutive uppercase)
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);
        return strtolower($value);
    }

    /** Convert value to PascalCase. */
    public static function pascal(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /** Convert value to kebab-case. */
    public static function kebab(string $value): string
    {
        // Insert hyphen before uppercase letters that follow lowercase or digits
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value);
        // Insert hyphen before uppercase letters that are followed by lowercase (for consecutive uppercase)
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $value);
        return strtolower($value);
    }

    /**
     * Convert singular word to plural form.
     *
     * Uses simplified English rules and intentionally does not cover irregular forms.
     */
    public static function plural(string $word): string
    {
        if (strlen($word) < 3) {
            return $word;
        }

        $isUpperCase = ctype_upper($word);
        $firstUpper = ctype_upper($word[0]);
        $lowerWord = strtolower($word);

        $lastChar = $lowerWord[strlen($lowerWord) - 1];

        // Words ending in 'y' -> 'ies'
        if ($lastChar === 'y') {
            $result = substr($lowerWord, 0, -1) . 'ies';
        } // Words ending in 's', 'x', 'z', 'o', 'ch', 'sh' -> 'es'
        elseif (in_array($lastChar, ['s', 'x', 'z', 'o'], true) || str_ends_with($lowerWord, 'ch') || str_ends_with($lowerWord, 'sh')) {
            $result = "{$lowerWord}es";
        } // Other words -> add 's'
        else {
            $result = "{$lowerWord}s";
        }

        // Preserve original case
        if ($isUpperCase) {
            return strtoupper($result);
        } elseif ($firstUpper) {
            return ucfirst($result);
        }
        return $result;
    }

    /**
     * Convert plural word to singular form.
     *
     * Uses simplified English rules and intentionally does not cover irregular forms.
     */
    public static function singular(string $word): string
    {
        if (strlen($word) < 3) {
            return $word;
        }

        $isUpperCase = ctype_upper($word);
        $firstUpper = ctype_upper($word[0]);
        $lowerWord = strtolower($word);

        // Words ending in 'ies' -> 'y'
        if (str_ends_with($lowerWord, 'ies') && strlen($lowerWord) >= 4) {
            $result = substr($lowerWord, 0, -3) . 'y';
        } // Words ending in 'es' and length >= 4
        elseif (str_ends_with($lowerWord, 'es') && strlen($lowerWord) >= 4) {
            // Check if we should remove 'es' or only 's'
            // Remove 'es' if the char before 'es' is 's', 'x', 'z', 'o', or ends with 'ch'/'sh'
            // For 'classes': length 7, char before 'es' is at index 4 (third from end)
            $charBeforeEs = $lowerWord[strlen($lowerWord) - 3];
            $shouldRemoveEs = in_array($charBeforeEs, ['s', 'x', 'z', 'o'], true)
                || str_ends_with($lowerWord, 'ches')
                || str_ends_with($lowerWord, 'shes');

            // Remove 'es' (2 chars) if shouldRemoveEs, otherwise remove 's' (1 char)
            $result = substr($lowerWord, 0, -($shouldRemoveEs ? 2 : 1));
        } // Words ending in 's' and length >= 2 -> remove 's'
        elseif (str_ends_with($lowerWord, 's') && strlen($lowerWord) >= 2) {
            $result = substr($lowerWord, 0, -1);
        } // Other words -> unchanged
        else {
            $result = $lowerWord;
        }

        // Preserve original case
        if ($isUpperCase) {
            return strtoupper($result);
        } elseif ($firstUpper) {
            return ucfirst($result);
        }
        return $result;
    }
}
