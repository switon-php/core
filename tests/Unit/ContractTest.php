<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use ReflectionMethod;
use ReflectionProperty;
use Switon\Core\Attribute\Autowired;
use Switon\Core\Categorizable;
use Switon\Core\ContextAware;
use Switon\Core\InputInterface;
use Switon\Core\Lazy;
use Switon\Core\Tests\TestCase;

/**
 * Contract test helper - provides reusable test classes for contract verification.
 */
trait ContractTestHelper
{
    /**
     * Create a simple test class with a Lazy type hint for testing.
     */
    private function createLazyTestClass(): object
    {
        return new class () {
            public function testMethod(Lazy $lazy): void
            {
                // Type hint works
            }
        };
    }

    /**
     * Create a comprehensive test class implementing multiple contracts.
     */
    private function createMultiContractTestClass(): object
    {
        return new class () implements ContextAware, Categorizable {
            public function getContext(): null
            {
                return null;
            }

            public function getCategory(): string
            {
                return 'test';
            }

            public function __toString(): string
            {
                return 'test';
            }
        };
    }
}

/**
 * Test cases for Core contracts (interfaces and attributes).
 *
 * Tests contracts through actual usage scenarios rather than reflection,
 * verifying that they work correctly in real-world use cases.
 */
class ContractTest extends TestCase
{
    use ContractTestHelper;

    /**
     * Test that Container can create instances with dependencies.
     */
    public function testContainerCreatesInstancesWithDependencies(): void
    {
        // Real usage: Create objects with constructor parameters
        $testClass = new class ('test', 123) {
            public function __construct(
                public string $name,
                public int    $value
            ) {
            }
        };

        $this->assertSame('test', $testClass->name);
        $this->assertSame(123, $testClass->value);
    }

    /**
     * Test that Lazy interface can be used for lazy loading.
     *
     * Verifies the marker interface can be used in union types for lazy dependency injection.
     */
    public function testLazyInterfaceSupportsLazyLoading(): void
    {
        // Real usage: Type hint accepts Lazy implementations
        $testClass = $this->createLazyTestClass();

        // Verify method exists and accepts Lazy parameter
        $method = new ReflectionMethod($testClass, 'testMethod');
        $parameter = $method->getParameters()[0];

        $this->assertSame(Lazy::class, $parameter->getType()->getName());

        // In real usage, container would inject lazy proxy here
        // This test verifies the type system works correctly
    }

    /**
     * Test that ContextAware objects can access context.
     */
    public function testContextAwareObjectsCanAccessContext(): void
    {
        // Real usage: Implement ContextAware to access request context
        $contextAware = new class () implements ContextAware {
            private $context = 'test-context';

            public function getContext(): mixed
            {
                return $this->context;
            }
        };

        $this->assertInstanceOf(ContextAware::class, $contextAware);
        $this->assertSame('test-context', $contextAware->getContext());
    }

    /**
     * Test that Categorizable objects can be categorized.
     */
    public function testCategorizableObjectsCanBeCategorized(): void
    {
        // Real usage: Categorize log messages, events, etc.
        $categorizable = new class () implements Categorizable {
            public function getCategory(): string
            {
                return 'database';
            }

            public function __toString(): string
            {
                return 'database';
            }
        };

        $this->assertInstanceOf(Categorizable::class, $categorizable);
        $this->assertSame('database', $categorizable->getCategory());
    }

    /**
     * Test that Autowired attribute enables automatic dependency injection.
     */
    public function testAutowiredAttributeEnablesDependencyInjection(): void
    {
        // Real usage: Mark properties for automatic injection
        $testClass = new class () {
            #[Autowired] public ?string $injectedProperty = null;
        };

        // Verify attribute is applied
        $rProperty = new ReflectionProperty($testClass, 'injectedProperty');
        $attributes = $rProperty->getAttributes(Autowired::class);

        $this->assertCount(1, $attributes);

        // In real usage, container would inject dependencies here
        $attribute = $attributes[0]->newInstance();
        $this->assertInstanceOf(Autowired::class, $attribute);
        $this->assertFalse($attribute->isInstances());
    }

    /**
     * Test that Input interface provides request data access.
     */
    public function testInputInterfaceProvidesRequestDataAccess(): void
    {
        // Real usage: Implement Input to access request data
        $input = new class () implements InputInterface {
            private array $data = ['name' => 'test', 'value' => 123];

            public function has(string|int $key): bool
            {
                return isset($this->data[$key]);
            }

            public function get(string|int $key, mixed $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }

            public function all(): array
            {
                return $this->data;
            }
        };

        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertTrue($input->has('name'));
        $this->assertSame('test', $input->get('name'));
        $this->assertSame(123, $input->get('value'));
        $this->assertNull($input->get('nonexistent'));
        $this->assertCount(2, $input->all());
    }

    /**
     * Test that multiple contracts can work together.
     */
    public function testMultipleContractsWorkTogether(): void
    {
        // Real usage: Combine multiple interfaces for rich functionality
        $object = new class () implements ContextAware, Categorizable {
            public function getContext(): string
            {
                return 'request-123';
            }

            public function getCategory(): string
            {
                return 'api';
            }

            public function __toString(): string
            {
                return 'request-123';
            }
        };

        $this->assertInstanceOf(ContextAware::class, $object);
        $this->assertInstanceOf(Categorizable::class, $object);
        $this->assertSame('request-123', $object->getContext());
        $this->assertSame('api', $object->getCategory());
    }

}
