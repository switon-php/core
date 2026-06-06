<?php

declare(strict_types=1);

namespace Switon\Core;

use ReflectionClass;

/**
 * Property-injection contract used by <code>#[Autowired]</code>.
 *
 * Use when infrastructure code needs to inject object properties from container
 * services and runtime parameters.
 * Keep it at construction boundaries; do not use it as a general object mutator.
 *
 * Road-signs:
 * - <code>inject()</code> populates <code>#[Autowired]</code> properties
 * - <code>resolveDependency()</code> follows explicit value → named alias → plain type
 * - provider/bootstrap lifecycle calls this before <code>boot()</code>
 * - scalars and arrays come from parameters or defaults, not service lookup
 *
 * @see \Switon\Core\Attribute\Autowired
 * @see \Switon\Core\InjectorInterface::inject()
 * @see \Switon\Kernel\ServiceBootstrapper::bootstrap()
 * @see \Switon\Kernel\Kernel::bootstrap()
 * @see \Switon\Di\Injector
 */
interface InjectorInterface
{
    /**
     * Inject properties marked with <code>#[Autowired]</code>.
     *
     * Use this for construction-time property wiring only.
     *
     * @param object $object Target object
     * @param array<string, mixed> $parameters Runtime overrides keyed by property name
     * @param ReflectionClass<object>|null $rClass Optional reflection cache
     */
    public function inject(object $object, array $parameters = [], ?ReflectionClass $rClass = null): void;

    /**
     * Resolve one dependency by type and member name.
     *
     * Resolution order is typically explicit <code>$value</code> → named alias
     * (<code>Type#name</code>) → plain type.
     * Use this for autowiring and container-facing factory paths, not general application lookups.
     *
     * @param string $type Service type (class/interface)
     * @param string $name Property/parameter name
     * @param string|null $value Explicit service ID or relative alias (for example <code>#primary</code>)
     *
     * @return object Resolved dependency instance
     */
    public function resolveDependency(string $type, string $name, ?string $value): object;
}
