<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Runtime;
use Switon\Core\Tests\TestCase;
use ReflectionClass;

/**
 * Test cases for Runtime class.
 *
 * Tests runtime environment state management including coroutine mode
 * and project root detection.
 */
class RuntimeTest extends TestCase
{
    /**
     * Reset Runtime state before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetRuntimeState();
        putenv('SWITON_ROOT');
    }

    /**
     * Clean up after each test to prevent global state pollution.
     */
    protected function tearDown(): void
    {
        $this->resetRuntimeState();
        putenv('SWITON_ROOT');
        parent::tearDown();
    }

    /**
     * Reset Runtime static properties to default values.
     *
     * Note: Reflection is necessary here because Runtime is a static-only class
     * that stores process-level state. Resetting static state for test isolation
     * is a legitimate use of Reflection.
     */
    protected function resetRuntimeState(): void
    {
        $reflection = new ReflectionClass(Runtime::class);

        $coroutineProperty = $reflection->getProperty('coroutineEnabled');
        $coroutineProperty->setValue(null, false);

        $rootProperty = $reflection->getProperty('root');
        $rootProperty->setValue(null, null);
    }

    /**
     * Test that setCoroutineEnabled() and isCoroutineEnabled() work correctly.
     */
    public function testSetAndGetCoroutineEnabled(): void
    {
        // Default should be false
        $this->assertFalse(Runtime::isCoroutineEnabled());

        // Enable coroutine
        Runtime::setCoroutineEnabled(true);
        $this->assertTrue(Runtime::isCoroutineEnabled());

        // Disable coroutine
        Runtime::setCoroutineEnabled(false);
        $this->assertFalse(Runtime::isCoroutineEnabled());
    }

    /**
     * Test that getRoot() returns cached value on subsequent calls.
     */
    public function testGetRootCachesResult(): void
    {
        // Set root manually to avoid detection logic
        Runtime::setRoot('/test/root');

        $root1 = Runtime::getRoot();
        $root2 = Runtime::getRoot();

        $this->assertSame($root1, $root2);
        $this->assertSame('/test/root', $root1);
    }

    /**
     * Test that setRoot() normalizes path separators.
     */
    public function testSetRootNormalizesPathSeparators(): void
    {
        Runtime::setRoot('C:\\Users\\myapp\\');

        $root = Runtime::getRoot();
        $this->assertSame('C:/Users/myapp', $root);
    }

    /**
     * Test that setRoot() trims trailing slashes.
     */
    public function testSetRootTrimsTrailingSlashes(): void
    {
        Runtime::setRoot('/var/www/myapp/');

        $root = Runtime::getRoot();
        $this->assertSame('/var/www/myapp', $root);
    }

    /**
     * Test that getRoot() uses environment variable when set.
     */
    public function testGetRootFromEnvironmentVariable(): void
    {
        // Set environment variable
        putenv('SWITON_ROOT=/custom/root/path');

        try {
            $root = Runtime::getRoot();
            $this->assertSame('/custom/root/path', $root);
        } finally {
            // Clean up
            putenv('SWITON_ROOT');
        }
    }

    /**
     * Test that getRoot() normalizes environment variable path.
     */
    public function testGetRootNormalizesEnvironmentVariablePath(): void
    {
        // Set environment variable with backslashes and trailing slash
        putenv('SWITON_ROOT=C:\\Users\\myapp\\');

        try {
            $root = Runtime::getRoot();
            $this->assertSame('C:/Users/myapp', $root);
        } finally {
            // Clean up
            putenv('SWITON_ROOT');
        }
    }

    /**
     * Test that getRoot() detects root from autoload.php in included files.
     */
    public function testGetRootFromAutoloadPhp(): void
    {
        // This test relies on autoload.php being in included files
        // which should be true in normal test execution
        $root = Runtime::getRoot();

        $this->assertIsString($root);
        $this->assertNotEmpty($root);
        $this->assertStringNotContainsString('\\', $root); // Should use forward slashes
        $this->assertStringEndsWithNot('/', $root); // Should not end with slash
    }

    /**
     * Test that setRoot() overrides automatic detection.
     */
    public function testSetRootOverridesAutomaticDetection(): void
    {
        // First get automatic detection result
        $autoRoot = Runtime::getRoot();

        // Reset cache to test override
        $this->resetRuntimeState();

        // Set custom root
        Runtime::setRoot('/custom/override/path');

        $root = Runtime::getRoot();
        $this->assertSame('/custom/override/path', $root);
        $this->assertNotSame($autoRoot, $root);
    }

    /**
     * Test that coroutine state is process-level (not reset between calls).
     */
    public function testCoroutineStateIsPersistent(): void
    {
        Runtime::setCoroutineEnabled(true);

        // Call multiple times
        $this->assertTrue(Runtime::isCoroutineEnabled());
        $this->assertTrue(Runtime::isCoroutineEnabled());
        $this->assertTrue(Runtime::isCoroutineEnabled());

        Runtime::setCoroutineEnabled(false);

        $this->assertFalse(Runtime::isCoroutineEnabled());
        $this->assertFalse(Runtime::isCoroutineEnabled());
    }

    /**
     * Test that getRoot() result is persistent across calls.
     */
    public function testGetRootResultIsPersistent(): void
    {
        Runtime::setRoot('/test/path');

        $root1 = Runtime::getRoot();
        $root2 = Runtime::getRoot();
        $root3 = Runtime::getRoot();

        $this->assertSame('/test/path', $root1);
        $this->assertSame($root1, $root2);
        $this->assertSame($root2, $root3);
    }

    /**
     * Test that environment variable takes precedence over autoload.php detection.
     */
    public function testEnvironmentVariableTakesPrecedence(): void
    {
        putenv('SWITON_ROOT=/env/var/path');

        try {
            // Reset cache to test environment variable priority
            $this->resetRuntimeState();

            $root = Runtime::getRoot();

            // Should use environment variable, not autoload.php detection
            $this->assertSame('/env/var/path', $root);
        } finally {
            putenv('SWITON_ROOT');
        }
    }

    /**
     * Helper method to check string does not end with suffix.
     */
    private function assertStringEndsWithNot(string $suffix, string $string): void
    {
        $this->assertFalse(
            str_ends_with($string, $suffix),
            "Failed asserting that '$string' does not end with '$suffix'"
        );
    }
}
