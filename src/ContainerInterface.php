<?php

declare(strict_types=1);

namespace Switon\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency injection container contract.
 *
 * Use when bootstrap code needs to register services, resolve services by ID,
 * or create fresh instances through DI.
 * Do not use as a general service locator in ordinary app code.
 *
 * Road-signs:
 * - <code>get()</code> resolves shared services by ID
 * - <code>set()</code> registers bindings and aliases
 * - same-namespace <code>XxxInterface</code> → <code>Xxx</code> auto-map in default container
 * - <code>make()</code> creates fresh instances through <code>MakerInterface</code>
 * - <code>Kernel\ServiceBootstrapper</code> wires provider registrations into the container
 *
 * Caller guidance:
 * - Prefer class/interface FQCN IDs for static analysis and refactoring safety.
 * - Prefer interface IDs first; conventional <code>XxxInterface</code> → <code>Xxx</code> mapping is automatic.
 * - Prefer alias IDs for named/multi-instance bindings (for example <code>Type#cache</code>).
 *
 * @see \Switon\Core\ContainerInterface::set()
 * @see \Switon\Core\ContainerInterface::get()
 * @see \Switon\Kernel\ServiceBootstrapper::bootstrap()
 * @see \Switon\Di\Container
 * @see \Switon\Core\MakerInterface
 */
interface ContainerInterface extends PsrContainerInterface, MakerInterface
{
    /**
     * Resolve a service by ID.
     *
     * Implementations usually cache the first resolved instance and return the
     * same instance on subsequent calls.
     * Use this for shared services, not per-call factories.
     *
     * @param string $id Service ID (prefer class/interface FQCN)
     *
     * @return mixed Resolved service instance
     *
     * @throws NotFoundExceptionInterface If no entry is found for this ID
     * @throws ContainerExceptionInterface If container cannot resolve this ID
     */
    public function get(string $id): mixed;

    /**
     * Check whether a service ID is resolvable.
     *
     * Use this for guard checks only; do not branch core logic on it when a direct lookup is acceptable.
     *
     * @param string $id Service ID
     *
     * @return bool True when the container can resolve this ID
     */
    public function has(string $id): bool;

    /**
     * Register a service definition.
     *
     * Definitions usually resolve to a class-string, object singleton, configuration
     * array, factory object, or service reference (for example <code>#other</code>).
     * Named variants should use <code>Type#name</code> IDs instead of ad hoc keys.
     *
     * @param string $id Service ID
     * @param mixed $definition Service definition
     *
     * @return static Container instance for chaining
     */
    public function set(string $id, mixed $definition): static;
}
