<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Time source contract.
 *
 * Use to keep time-dependent code testable by swapping clock implementations.
 *
 * Guidance: Read current time through this contract instead of direct global functions when behavior depends on time.
 *
 * @see \Switon\Core\Clock
 * @see \Switon\Testing\MockClock
 */
interface ClockInterface
{
    /** Return Unix timestamp in seconds. */
    public function time(): int;

    /** Return Unix timestamp with microsecond precision. */
    public function microtime(): float;
}
