<?php

declare(strict_types=1);

namespace Switon\Core;

use function microtime;
use function time;

/**
 * System-time implementation of `ClockInterface`.
 *
 * @see \Switon\Core\ClockInterface
 * @see \Switon\Testing\MockClock
 */
class Clock implements ClockInterface
{
    /**
     * {@inheritDoc}
     */
    public function time(): int
    {
        return time();
    }

    /**
     * {@inheritDoc}
     */
    public function microtime(): float
    {
        return microtime(true);
    }
}
