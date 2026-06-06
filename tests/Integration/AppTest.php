<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Integration;

use Switon\Core\App;
use Switon\Core\ContainerInterface;
use Switon\Core\Exception;
use Switon\Core\Tests\Fixtures\TestClass;
use Switon\Core\Tests\TestCase;
use RuntimeException;

/**
 * Test container implementation for App testing.
 */
class TestContainer implements \Psr\Container\ContainerInterface, ContainerInterface
{
    private array $services = [];

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Service '$id' not found");
        }
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function set(string $id, mixed $definition): static
    {
        $this->services[$id] = $definition;
        return $this;
    }

    public function make(string $name, array $parameters = []): mixed
    {
        // Simple implementation for testing
        if ($name === TestClass::class) {
            return new TestClass(
                $parameters['name'] ?? 'default',
                $parameters['value'] ?? 0
            );
        }
        throw new RuntimeException("Cannot make: $name");
    }

}

/**
 * Test cases for App class static methods.
 *
 * Tests container access via static methods.
 */
class AppTest extends TestCase
{
    protected ?ContainerInterface $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test container
        $container = new TestContainer();
        App::setContainer($container);
    }

    protected function tearDown(): void
    {
        // Clear container
        App::setContainer(new TestContainer());

        parent::tearDown();
    }

    /**
     * Test that get() retrieves service from container.
     */
    public function testGetRetrievesServiceFromContainer(): void
    {
        $container = new TestContainer();
        $container->set(ContainerInterface::class, $container);
        App::setContainer($container);

        $result = App::get(ContainerInterface::class);

        $this->assertInstanceOf(ContainerInterface::class, $result);
        $this->assertSame($container, $result);
    }

    /**
     * Test that make() creates new instance with container.
     */
    public function testMakeCreatesNewInstance(): void
    {
        $container = new TestContainer();
        $container->set(ContainerInterface::class, $container);
        App::setContainer($container);

        // Create a simple test class
        $instance = App::make(TestClass::class, ['name' => 'test', 'value' => 123]);

        $this->assertInstanceOf(TestClass::class, $instance);
        $this->assertSame('test', $instance->name);
        $this->assertSame(123, $instance->value);
    }

    /**
     * Test that getContainer() returns the container instance.
     */
    public function testGetContainerReturnsContainerInstance(): void
    {
        // Arrange
        $container = new TestContainer();
        App::setContainer($container);

        // Act
        $result = App::getContainer();

        // Assert
        $this->assertSame($container, $result);
    }

    /**
     * Test that getContainer() returns null when no container is set.
     */
    public function testGetContainerReturnsNullWhenNotSet(): void
    {
        // Arrange
        App::setContainer(null);

        // Act
        $result = App::getContainer();

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test that get() throws Exception when container is not set.
     */
    public function testGetThrowsExceptionWhenContainerNotSet(): void
    {
        // Arrange
        App::setContainer(null);

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Container not initialized/');

        // Act
        App::get(ContainerInterface::class);
    }

    /**
     * Test that make() throws Exception when container is not set.
     */
    public function testMakeThrowsExceptionWhenContainerNotSet(): void
    {
        // Arrange
        App::setContainer(null);

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Container not initialized/');

        // Act
        App::make(TestClass::class);
    }

}
