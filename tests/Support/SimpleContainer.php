<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Support;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionNamedType;
use Switon\Core\App;
use Switon\Core\Attribute\Autowired;
use Switon\Core\ClassName;
use Switon\Core\ContainerInterface;
use Switon\Core\InjectorInterface;
use Switon\Core\Lazy;
use Switon\Core\MakerInterface;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;

final class SimpleContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    private ?SimpleInjector $injector = null;

    /**
     * @param array<string, mixed> $definitions
     */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $id => $definition) {
            $this->definitions[$id] = $definition;
        }

        $this->instances[self::class] = $this;
        $this->instances[ContainerInterface::class] = $this;
        $this->instances[MakerInterface::class] = $this;
        $this->instances[InjectorInterface::class] = $this->getInjector();
    }

    public function set(string $id, mixed $definition): static
    {
        if (ClassName::isAutoMapPair($id, $definition)) {
            return $this;
        }

        if (is_array($definition) && !isset($definition['class'])) {
            $existing = $this->definitions[$id] ?? null;
            if (is_string($existing)) {
                $definition['class'] = $existing;
            } elseif (is_array($existing) && isset($existing['class'])) {
                $definition['class'] = $existing['class'];
            }
        }

        $this->definitions[$id] = $definition;
        unset($this->instances[$id]);

        return $this;
    }

    public function replace(string $id, mixed $definition): static
    {
        return $this->set($id, $definition);
    }

    public function remove(string $id): static
    {
        unset($this->definitions[$id], $this->instances[$id]);

        return $this;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->definitions[$id])
            || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $resolved = $this->resolveDefinition($id, $this->definitions[$id]);
            $this->instances[$id] = $resolved;

            return $resolved;
        }

        if (interface_exists($id)) {
            $className = substr($id, 0, -9);
            if (class_exists($className)) {
                $instance = $this->createInstance($className);
                $this->instances[$id] = $instance;
                return $instance;
            }
        }

        if (!str_ends_with($id, 'Interface')) {
            $interfaceName = $id . 'Interface';
            if (interface_exists($interfaceName)) {
                return $this->get($interfaceName);
            }
        }

        if (class_exists($id)) {
            $instance = $this->createInstance($id);
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new class ("Service \"$id\" not found") extends RuntimeException implements NotFoundExceptionInterface {
        };
    }

    public function make(string $name, array $parameters = []): mixed
    {
        return $this->createInstance($name, $parameters);
    }

    public function inject(object $object, array $parameters = [], ?ReflectionClass $rClass = null): void
    {
        $this->getInjector()->inject($object, $parameters, $rClass);
    }

    public function resolveDependency(string $type, string $name, ?string $value): object
    {
        return $this->getInjector()->resolveDependency($type, $name, $value);
    }

    private function getInjector(): SimpleInjector
    {
        return $this->injector ??= new SimpleInjector($this);
    }

    private function resolveDefinition(string $id, mixed $definition): mixed
    {
        if (is_object($definition)) {
            return $definition;
        }

        if (is_string($definition)) {
            if (class_exists($definition)) {
                return $this->createInstance($definition);
            }

            return $this->get($definition);
        }

        if (is_array($definition)) {
            $class = $definition['class'] ?? $id;
            if (!is_string($class) || !class_exists($class)) {
                throw new class ("Invalid definition for \"$id\"") extends RuntimeException implements ContainerExceptionInterface {
                };
            }

            unset($definition['class']);

            return $this->createInstance($class, $definition);
        }

        return $definition;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createInstance(string $className, array $parameters = []): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            $instance = $reflection->newInstance();
        } else {
            $arguments = [];
            foreach ($constructor->getParameters() as $parameter) {
                $parameterName = $parameter->getName();

                if (array_key_exists($parameterName, $parameters)) {
                    $arguments[] = $parameters[$parameterName];
                    continue;
                }

                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $arguments[] = $this->get($type->getName());
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new class ("Cannot resolve constructor parameter \"$parameterName\" for \"$className\"") extends RuntimeException implements ContainerExceptionInterface {
                };
            }

            $instance = $reflection->newInstanceArgs($arguments);
        }

        $this->inject($instance, $parameters, $reflection);

        if ($instance instanceof App) {
            App::setContainer($this);
        }

        return $instance;
    }
}

final class SimpleInjector implements InjectorInterface
{
    public function __construct(private readonly SimpleContainer $container)
    {
    }

    public function inject(object $object, array $parameters = [], ?ReflectionClass $rClass = null): void
    {
        $rClass ??= new ReflectionClass($object);

        foreach ($rClass->getProperties() as $property) {
            if ($property->getAttributes(Autowired::class) === []) {
                continue;
            }

            $propertyName = $property->getName();
            $resolved = $this->resolvePropertyValue($property, $parameters);

            if ($resolved === Unresolved::Value) {
                continue;
            }

            $property->setValue($object, $resolved);
        }
    }

    public function resolveDependency(string $type, string $name, ?string $value): object
    {
        if ($value !== null && $value !== '') {
            $serviceId = str_starts_with($value, '#') ? $type . $value : $value;
            return $this->container->get($serviceId);
        }

        $namedService = $type . '#' . $name;
        if ($this->container->has($namedService)) {
            return $this->container->get($namedService);
        }

        return $this->container->get($type);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function resolvePropertyValue(ReflectionProperty $property, array $parameters): mixed
    {
        $propertyName = $property->getName();

        if (array_key_exists($propertyName, $parameters)) {
            return $parameters[$propertyName];
        }

        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return Unresolved::Value;
            }

            return $this->container->get($type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $namedType) {
                if (!$namedType instanceof ReflectionNamedType || $namedType->isBuiltin()) {
                    continue;
                }

                if ($namedType->getName() === Lazy::class) {
                    continue;
                }

                if ($this->container->has($namedType->getName())) {
                    return $this->container->get($namedType->getName());
                }
            }
        }

        return Unresolved::Value;
    }
}

enum Unresolved
{
    case Value;
}
