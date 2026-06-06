<?php

declare(strict_types=1);

namespace Switon\Core;

use function preg_match;
use function preg_quote;
use function str_contains;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Shared filter matching: substring (case-insensitive) or wildcards `*` and `?`.
 *
 * @see \Switon\Core\FilterMatcherInterface
 */
class FilterMatcher implements FilterMatcherInterface
{
    public function match(string $filter, string $subject): bool
    {
        if (trim($filter) === '') {
            return true;
        }
        if (str_contains($filter, '*') || str_contains($filter, '?')) {
            $regex = preg_quote($filter, '#');
            $regex = str_replace(['\*', '\?'], ['.*', '.'], $regex);
            return (bool)preg_match('#' . $regex . '#i', $subject);
        }
        return str_contains(strtolower($subject), strtolower($filter));
    }

    /**
     * @param string|array<string|int, mixed> $subjects
     */
    public function matchAny(string $filter, string|array $subjects): bool
    {
        if (trim($filter) === '') {
            return true;
        }
        if (is_string($subjects)) {
            return $this->match($filter, $subjects);
        }

        foreach ($subjects as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                if ($this->matchAny($filter, $value)) {
                    return true;
                }
                continue;
            }

            if (is_string($value) && $this->match($filter, $value)) {
                return true;
            }
        }
        return false;
    }
}
