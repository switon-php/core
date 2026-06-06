<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Marker for lazily-resolved autowired dependencies.
 *
 * Use with union types such as `CacheInterface|Lazy` to allow delayed resolution.
 *
 * @see \Switon\Core\Attribute\Autowired
 * @see \Switon\Di\LazyPropertyProxy
 */
interface Lazy
{
}
