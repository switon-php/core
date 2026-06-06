<?php

declare(strict_types=1);

namespace Switon\Core;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Exception\RuntimeException;

/**
 * Application metadata service with a static DI bridge for bootstrap edges.
 *
 * Read app metadata through <code>AppInterface</code>.
 * Guidance: Prefer injected <code>AppInterface</code> or <code>ContainerInterface</code> in normal code; use <code>App::get()</code> and <code>App::make()</code> only when the static bridge is already wired.
 *
 * Road-signs:
 * - app metadata only
 * - static bridge via <code>setContainer()</code>
 * - <code>get()</code> returns shared services
 * - <code>make()</code> returns fresh instances
 * - app metadata properties are autowired, not public API knobs
 *
 * @see \Switon\Core\AppInterface
 * @see \Switon\Core\ContainerInterface
 * @see \Switon\Kernel\Kernel
 * @see \Switon\Kernel\Config
 * @see \Switon\Kernel\Env
 */
class App implements AppInterface
{
    /**
     * Container instance stored as static property.
     */
    protected static ?ContainerInterface $container = null;

    #[Autowired] protected string $id = 'switon';
    #[Autowired] protected string $name = 'Switon Application';
    #[Autowired] protected string $version = '1.0.0';
    #[Autowired] protected string $env = 'prod';
    #[Autowired] protected bool $debug = false;
    #[Autowired] protected string $timezone = 'UTC';

    /**
     * Set the container instance.
     *
     * Bootstrap-only bridge for wiring the static helper entrypoints.
     *
     * @param ContainerInterface|null $container The container instance
     */
    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the container instance.
     *
     * @return ContainerInterface|null The container instance
     */
    public static function getContainer(): ?ContainerInterface
    {
        return self::$container;
    }

    /**
     * Resolve a service from the static container.
     *
     * Bootstrap-only bridge for framework edges and compatibility helpers.
     * Prefer injected <code>ContainerInterface</code> or <code>MakerInterface</code> in normal code.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @phpstan-return T
     *
     * @throws \Switon\Core\Exception\RuntimeException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public static function get(string $id): mixed
    {
        if (self::$container === null) {
            RuntimeException::raise('Container not initialized, call App::setContainer() before using App::get()');
        }
        return self::$container->get($id);
    }

    /**
     * Create a fresh instance through the DI container.
     *
     * This uses the container <code>make()</code> path instead of cached service lookup.
     *
     * @template T of object
     *
     * @param class-string<T> $name
     * @param array<string, mixed> $parameters
     *
     * @phpstan-return T
     *
     * @throws \Switon\Core\Exception\RuntimeException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function make(string $name, array $parameters = []): mixed
    {
        if (self::$container === null) {
            RuntimeException::raise('Container not initialized, call App::setContainer() before using App::make()');
        }
        return self::$container->make($name, $parameters);
    }

    /**
     * Get application identifier.
     *
     * @return string Application ID
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get application display name.
     *
     * @return string Application name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get application version.
     *
     * @return string Application version
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Get environment name.
     *
     * @return string Environment name (e.g., 'prod', 'dev', 'test')
     */
    public function env(): string
    {
        return $this->env;
    }

    /**
     * Check if current environment matches the given environment name.
     *
     * @param string $env Environment name to check (e.g., 'prod', 'dev', 'test')
     *
     * @return bool True if current environment matches, false otherwise
     */
    public function isEnv(string $env): bool
    {
        return $this->env === $env;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Get application timezone.
     *
     * @return string Timezone identifier (e.g., 'UTC', 'Asia/Shanghai')
     */
    public function timezone(): string
    {
        return $this->timezone;
    }
}
