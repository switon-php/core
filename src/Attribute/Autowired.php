<?php

declare(strict_types=1);

namespace Switon\Core\Attribute;

use Attribute;

/**
 * Marks a property for container injection.
 *
 * @see \Switon\Core\InjectorInterface
 * @see \Switon\Core\Lazy
 * @see \Switon\Di\Injector
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Autowired
{
    /**
     * @param bool $instances True to resolve array items as service instances; false for plain config array.
     */
    public function __construct(
        protected bool $instances = false
    ) {
    }

    /** Whether array items should be resolved as service instances. */
    public function isInstances(): bool
    {
        return $this->instances;
    }
}
