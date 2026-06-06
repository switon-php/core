<?php

declare(strict_types=1);

namespace Switon\Core\Attribute;

use Attribute;

/**
 * Declares a provider-owned resource path alias for automatic registration.
 *
 * @see \Switon\Core\ResourceAliasRegistrar
 * @see \Switon\Kernel\ServiceBootstrapper
 * @see \Switon\Core\ServiceProviderInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ResourceAlias
{
    /**
     * @param string|null $alias Explicit alias override; null = auto-generate
     * @param string $path Relative path from package root; defaults to sibling `resources/`
     */
    public function __construct(
        public ?string $alias = null,
        public string  $path = 'resources',
    ) {
    }
}
