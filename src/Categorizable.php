<?php

declare(strict_types=1);

namespace Switon\Core;

use Stringable;

/**
 * Contract for values that expose a category string.
 *
 * Use when a value needs a stable category key for logging, filtering, or routing.
 *
 * @see \Switon\Core\Categorized
 * @see \Switon\Logging\LoggerInterface
 * @see \Switon\Eventing\EventLoggerInterface
 */
interface Categorizable extends Stringable
{
    /** Return category identifier. */
    public function getCategory(): string;
}
