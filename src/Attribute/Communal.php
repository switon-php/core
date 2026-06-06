<?php

declare(strict_types=1);

namespace Switon\Core\Attribute;

use Attribute;

/**
 * Marker for properties that are intentionally shared across requests/coroutines.
 *
 * @see \Switon\Core\ContextAware
 * @see \Switon\Core\ContextManagerInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Communal
{
    /** Marker attribute; no runtime payload. */
    public function __construct()
    {
    }
}
