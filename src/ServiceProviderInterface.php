<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Service-provider lifecycle contract.
 *
 * Register bindings in `register()`, then run initialization in `boot()`.
 *
 * Guidance: Keep container mutation in <code>register()</code>; use <code>boot()</code> for post-registration wiring only.
 *
 * @see \Switon\Core\ServiceProviderInterface::register()
 * @see \Switon\Core\ServiceProviderInterface::boot()
 * @see \Switon\Kernel\ServiceBootstrapper::bootstrap()
 * @see \Switon\Core\ResourceAliasRegistrar
 */
interface ServiceProviderInterface
{
    /** Register container bindings. */
    public function register(ContainerInterface $container): void;

    /** Run post-registration initialization. */
    public function boot(): void;
}
