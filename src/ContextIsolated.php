<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Marker for contexts that must not be inherited across coroutines.
 *
 * Use for non-shareable resources (for example connections/transactions) that
 * require strict coroutine isolation.
 *
 * @see \Switon\Core\ContextAware
 * @see \Switon\Core\ContextManagerInterface
 */
interface ContextIsolated
{
}
