<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Marker contract for components that keep mutable request or connection state in a context object.
 *
 * Use for state that must not live on a long-lived service property.
 *
 * Guidance: Return only the context object; creation and lifecycle management belong to the context manager.
 *
 * @see \Switon\Core\ContextManagerInterface
 * @see \Switon\Core\ContextIsolated
 * @see \Switon\Core\ContextConnScoped
 * @see \Switon\Http\Request
 * @see \Switon\Http\RequestContext
 */
interface ContextAware
{
    /**
     * Return current context object.
     *
     * @return mixed Context object for this component
     */
    public function getContext(): mixed;
}
