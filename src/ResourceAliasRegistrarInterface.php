<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Registers provider-declared resource aliases.
 *
 * @see \Switon\Core\Attribute\ResourceAlias
 * @see \Switon\Core\PathAliasInterface
 */
interface ResourceAliasRegistrarInterface
{
    /**
     * Register all declared package-resource aliases for one provider class.
     *
     * Missing attributes or unsupported directory layouts are ignored.
     *
     * @param class-string $providerClass
     */
    public function register(PathAliasInterface|Lazy $pathAlias, string $providerClass): void;
}
