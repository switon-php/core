<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Filter matching for substring and wildcard checks.
 *
 * @see \Switon\Core\FilterMatcher
 */
interface FilterMatcherInterface
{
    public function match(string $filter, string $subject): bool;

    /**
     * Match a filter against multiple subjects; returns true on first match.
     * Nested arrays are traversed recursively.
     *
     * @param string|array<string|int, mixed> $subjects
     */
    public function matchAny(string $filter, string|array $subjects): bool;
}
