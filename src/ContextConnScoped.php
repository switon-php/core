<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Marker for contexts that follow connection lifecycle.
 *
 * Use for long-lived connection state (for example WebSocket session state)
 * that should survive across multiple messages until disconnect.
 *
 * @see \Switon\Core\ContextIsolated
 * @see \Switon\Core\ContextManagerInterface
 * @see \Switon\Http\RequestContext
 */
interface ContextConnScoped
{
}
