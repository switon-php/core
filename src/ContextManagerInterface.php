<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Context manager contract for `ContextAware` components.
 *
 * Use when a long-lived service needs a dedicated context object for mutable state.
 * Guidance: Callers should treat the returned object as the mutable state boundary for the owner service.
 *
 * @see \Switon\Core\ContextAware
 * @see \Switon\Core\ContextConnScoped
 * @see \Switon\Core\ContextIsolated
 */
interface ContextManagerInterface
{
    /**
     * Get or create context object for one component instance.
     *
     * @param ContextAware $object Owner component
     * @param int $cid Coroutine ID hint (0 = current)
     *
     * @return mixed Context object (usually <code>ComponentNameContext</code>)
     */
    public function getContext(ContextAware $object, int $cid = 0): mixed;
}
