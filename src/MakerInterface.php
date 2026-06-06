<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Published fresh-instance creation protocol used by container, test, and runtime layers.
 *
 * Use when callers need a new instance on each call, not a cached singleton.
 *
 * Guidance: Inject <code>MakerInterface</code> as required; do not use nullable
 * <code>?MakerInterface</code> with <code>ReflectionAttribute::newInstance()</code> fallbacks.
 *
 * Road-signs:
 * - stateless reflection attribute (scalar ctor args only) → <code>ReflectionAttribute::newInstance()</code>
 * - class needs container-resolved ctor or <code>#[Autowired]</code> properties → <code>make()</code>
 * - shared service → <code>ContainerInterface::get()</code>, not <code>make()</code>
 *
 * @see \Switon\Core\ContainerInterface
 * @see \Switon\Di\Container::make()
 */
interface MakerInterface
{
    /**
     * Creates a new instance using dependency injection.
     *
     * Implementations are expected to always return a **fresh** instance for the
     * given name and to resolve constructor parameters (and any configured
     * property injections) according to the underlying container's rules.
     * Avoid using this for shared services.
     *
     * @template T of object
     *
     * @param class-string<T> $name Class name, interface name, or service alias.
     * @param array<int|string, mixed> $parameters Constructor parameters and property values.
     *
     * @phpstan-return T
     */
    public function make(string $name, array $parameters = []): mixed;
}
