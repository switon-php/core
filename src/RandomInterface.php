<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Random-source contract for secure generation and test substitution.
 *
 * Guidance: Use this contract for generated identifiers, tokens, and randomized testable behavior instead of direct global random helpers.
 *
 * @see \Switon\Core\Random
 * @see \Switon\Testing\MockRandom
 */
interface RandomInterface
{
    /** Generate random bytes. */
    public function bytes(int $length): string;

    /** Generate random integer in inclusive range. */
    public function int(int $min, int $max): int;

    /** Generate UUID v4 string. */
    public function uuid(): string;

    /** Generate random chars; `$base` is 2–62 (16 and 62 use optimized paths). */
    public function chars(int $length, int $base = 62): string;
}
