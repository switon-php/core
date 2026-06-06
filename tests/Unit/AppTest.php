<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\App;
use Switon\Core\Clock;
use Switon\Core\ClockInterface;
use Switon\Core\Exception;
use Switon\Core\Tests\TestCase;

/**
 * Test cases for App class.
 *
 * Tests application information container with default values and configuration.
 */
class AppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Container is already initialized by parent TestCase
    }

    /**
     * Test that App has default values when created without options.
     */
    public function testAppHasDefaultValues(): void
    {
        $app = $this->container->make(App::class);

        $this->assertSame('switon', $app->id());
        $this->assertSame('Switon Application', $app->name());
        $this->assertSame('1.0.0', $app->version());
        $this->assertSame('prod', $app->env());
        $this->assertFalse($app->isDebug());
        $this->assertSame('UTC', $app->timezone());
    }

    /**
     * Test that App can be configured with custom values.
     */
    public function testAppCanBeConfigured(): void
    {
        $app = $this->container->make(App::class, [
            'id' => 'myapp',
            'name' => 'My Application',
            'version' => '2.0.0',
            'env' => 'dev',
            'debug' => true,
            'timezone' => 'Asia/Shanghai',
        ]);

        $this->assertSame('myapp', $app->id());
        $this->assertSame('My Application', $app->name());
        $this->assertSame('2.0.0', $app->version());
        $this->assertSame('dev', $app->env());
        $this->assertTrue($app->isDebug());
        $this->assertSame('Asia/Shanghai', $app->timezone());
    }

    /**
     * Test that App ignores unknown properties in options.
     */
    public function testAppIgnoresUnknownProperties(): void
    {
        $app = $this->container->make(App::class, [
            'id' => 'myapp',
            'unknownProperty' => 'should be ignored',
        ]);

        // Verify that only expected properties are set
        $this->assertSame('myapp', $app->id());
        // Verify that unknown property was ignored (default values remain)
        $this->assertSame('Switon Application', $app->name());
        $this->assertSame('1.0.0', $app->version());
        $this->assertSame('prod', $app->env());
        $this->assertFalse($app->isDebug());
        $this->assertSame('UTC', $app->timezone());
    }

    /**
     * Test that App can be partially configured.
     */
    public function testAppCanBePartiallyConfigured(): void
    {
        $app = $this->container->make(App::class, [
            'id' => 'custom',
            'debug' => true,
        ]);

        $this->assertSame('custom', $app->id());
        $this->assertTrue($app->isDebug());
        // Other properties should have defaults
        $this->assertSame('Switon Application', $app->name());
        $this->assertSame('prod', $app->env());
    }

    /**
     * Test that isEnv() returns true when environment matches.
     */
    public function testIsEnvReturnsTrueWhenMatches(): void
    {
        // Arrange
        $app = $this->container->make(App::class, ['env' => 'dev']);

        // Act & Assert
        $this->assertTrue($app->isEnv('dev'), 'isEnv() should return true for matching environment');
    }

    /**
     * Test that isEnv() returns false when environment does not match.
     */
    public function testIsEnvReturnsFalseWhenNotMatches(): void
    {
        // Arrange
        $app = $this->container->make(App::class, ['env' => 'prod']);

        // Act & Assert
        $this->assertFalse($app->isEnv('dev'), 'isEnv() should return false for non-matching environment');
        $this->assertFalse($app->isEnv('test'), 'isEnv() should return false for non-matching environment');
    }

    /**
     * Test that isEnv() is case-sensitive.
     */
    public function testIsEnvIsCaseSensitive(): void
    {
        // Arrange
        $app = $this->container->make(App::class, ['env' => 'prod']);

        // Act & Assert
        $this->assertTrue($app->isEnv('prod'));
        $this->assertFalse($app->isEnv('Prod'), 'isEnv() should be case-sensitive');
        $this->assertFalse($app->isEnv('PROD'), 'isEnv() should be case-sensitive');
    }

    public function testStaticGetContainerReturnsCurrentContainer(): void
    {
        $this->assertSame($this->container, App::getContainer());
    }

    public function testStaticGetResolvesServiceFromContainer(): void
    {
        $clock = App::get(ClockInterface::class);

        $this->assertInstanceOf(Clock::class, $clock);
    }

    public function testStaticMakeBuildsInstanceWithParameters(): void
    {
        $app = App::make(App::class, [
            'id' => 'from-static-make',
            'env' => 'staging',
        ]);

        $this->assertInstanceOf(App::class, $app);
        $this->assertSame('from-static-make', $app->id());
        $this->assertSame('staging', $app->env());
    }

    public function testStaticGetThrowsWhenContainerNotSet(): void
    {
        App::setContainer(null);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Container not initialized');
            App::get(ClockInterface::class);
        } finally {
            App::setContainer($this->container);
        }
    }

    public function testStaticMakeThrowsWhenContainerNotSet(): void
    {
        App::setContainer(null);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Container not initialized');
            App::make(App::class);
        } finally {
            App::setContainer($this->container);
        }
    }
}
