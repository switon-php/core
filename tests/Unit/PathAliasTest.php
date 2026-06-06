<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use ReflectionMethod;
use ReflectionProperty;
use Switon\Core\Exception\AliasNotFoundException;
use Switon\Core\Exception\CircularAliasException;
use Switon\Core\Exception\InvalidAliasNameException;
use Switon\Core\PathAlias;
use Switon\Core\Tests\TestCase;

use function count;

/**
 * Test cases for PathAlias class.
 *
 * Tests path alias registration, resolution, and template placeholder replacement.
 */
class PathAliasTest extends TestCase
{
    protected PathAlias $pathAlias;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathAlias = new PathAlias();
    }

    /**
     * Test that set() registers a basic alias with absolute path.
     */
    public function testSetWithAbsolutePath(): void
    {
        $result = $this->pathAlias->set('@root', '/var/www/myapp');

        $this->assertSame('/var/www/myapp', $result);
        $this->assertTrue($this->pathAlias->has('@root'));
        $this->assertSame('/var/www/myapp', $this->pathAlias->get('@root'));
    }

    /**
     * Test that set() supports nested aliases.
     */
    public function testSetWithNestedAlias(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->pathAlias->set('@app', '@root/app');

        $this->assertSame('/var/www/myapp/app', $this->pathAlias->get('@app'));
    }

    /**
     * Test that set() throws InvalidAliasNameException for invalid alias names.
     */
    public function testSetThrowsExceptionForInvalidAliasName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->pathAlias->set('root', '/var/www/myapp');
    }

    /**
     * Test that resolve() resolves alias paths with subdirectories.
     */
    public function testResolveWithSubdirectories(): void
    {
        $this->pathAlias->set('@app', '/var/www/myapp/app');
        $result = $this->pathAlias->resolve('@app/config/database.php');

        $this->assertSame('/var/www/myapp/app/config/database.php', $result);
    }

    /**
     * Test that resolve() supports template placeholder replacement.
     */
    public function testResolveWithTemplatePlaceholders(): void
    {
        $this->pathAlias->set('@logs', '/var/logs');
        $result = $this->pathAlias->resolve('@logs/{date}.log', ['date' => '2024-01-15']);

        $this->assertSame('/var/logs/2024-01-15.log', $result);
    }

    /**
     * Test that resolve() returns non-alias paths as-is.
     */
    public function testResolveWithNonAliasPath(): void
    {
        $result = $this->pathAlias->resolve('/absolute/path/to/file.php');
        $this->assertSame('/absolute/path/to/file.php', $result);
    }

    /**
     * Test that resolve() throws AliasNotFoundException for non-existent alias.
     */
    public function testResolveThrowsExceptionForNonExistentAlias(): void
    {
        $this->expectException(AliasNotFoundException::class);
        $this->pathAlias->resolve('@nonexistent');
    }

    /**
     * Test that has() correctly checks alias existence.
     */
    public function testHasChecksAliasExistence(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->assertTrue($this->pathAlias->has('@root'));
        $this->assertFalse($this->pathAlias->has('@nonexistent'));
    }

    /**
     * Test that remove() removes registered aliases.
     */
    public function testRemoveRemovesAlias(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->assertTrue($this->pathAlias->has('@root'));

        $this->pathAlias->remove('@root');
        $this->assertFalse($this->pathAlias->has('@root'));
        $this->assertNull($this->pathAlias->get('@root'));
    }

    /**
     * Test that all() returns all registered aliases.
     */
    public function testAllReturnsAllAliases(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->pathAlias->set('@app', '@root/app');
        $this->pathAlias->set('@cache', '/var/cache');

        $all = $this->pathAlias->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('@root', $all);
        $this->assertArrayHasKey('@app', $all);
        $this->assertArrayHasKey('@cache', $all);
    }

    /**
     * Test that set() handles empty string paths.
     */
    public function testSetWithEmptyStringPath(): void
    {
        $result = $this->pathAlias->set('@empty', '');
        $this->assertSame('', $result);
        $this->assertSame('', $this->pathAlias->get('@empty'));
    }

    /**
     * Test that jsonSerialize() returns all aliases.
     */
    public function testJsonSerializeReturnsAllAliases(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->pathAlias->set('@app', '@root/app');

        $json = $this->pathAlias->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('@root', $json);
        $this->assertArrayHasKey('@app', $json);
    }

    /**
     * Test that resolve() throws exception when alias in path doesn't exist.
     */
    public function testResolveThrowsExceptionForNonExistentAliasInPath(): void
    {
        $this->expectException(AliasNotFoundException::class);
        $this->pathAlias->resolve('@nonexistent/sub/path');
    }

    /**
     * Test that set() normalizes path separators.
     */
    public function testSetNormalizesPathSeparators(): void
    {
        $result = $this->pathAlias->set('@root', 'C:\\Users\\myapp');

        $this->assertSame('C:/Users/myapp', $result);
        $this->assertSame('C:/Users/myapp', $this->pathAlias->get('@root'));
    }

    /**
     * Test that set() trims trailing slashes.
     */
    public function testSetTrimsTrailingSlashes(): void
    {
        $result = $this->pathAlias->set('@root', '/var/www/myapp/');

        $this->assertSame('/var/www/myapp', $result);
        $this->assertSame('/var/www/myapp', $this->pathAlias->get('@root'));
    }

    /**
     * Test that resolve() handles simple alias without subdirectories.
     */
    public function testResolveWithSimpleAlias(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $result = $this->pathAlias->resolve('@root');

        $this->assertSame('/var/www/myapp', $result);
    }

    /**
     * Test that constructor automatically sets built-in aliases (@root, @vendor, @runtime, @app, @resources).
     */
    public function testConstructorSetsBuiltInAliases(): void
    {
        $pathAlias = new PathAlias();

        $this->assertTrue($pathAlias->has('@root'));
        $this->assertTrue($pathAlias->has('@vendor'));
        $this->assertTrue($pathAlias->has('@runtime'));
        $this->assertTrue($pathAlias->has('@app'));
        $this->assertTrue($pathAlias->has('@resources'));

        $root = $pathAlias->get('@root');
        $this->assertIsString($root);
        $this->assertNotEmpty($root);
        $this->assertStringNotContainsString('@', $root); // Should be resolved, not contain @

        $vendor = $pathAlias->get('@vendor');
        $this->assertIsString($vendor);
        $this->assertStringEndsWith('/vendor', $vendor);
        $this->assertStringStartsWith($root, $vendor);

        $runtime = $pathAlias->get('@runtime');
        $this->assertIsString($runtime);
        $this->assertStringEndsWith('/runtime', $runtime);
        $this->assertStringStartsWith($root, $runtime);

        $resources = $pathAlias->get('@resources');
        $this->assertIsString($resources);
        $this->assertStringEndsWith('/resources', $resources);
        $this->assertStringStartsWith($root, $resources);
    }

    /**
     * Test that set() can override built-in aliases after construction.
     */
    public function testSetCanOverrideBuiltInAliases(): void
    {
        $pathAlias = new PathAlias();
        $originalRoot = $pathAlias->get('@root');

        // Verify we can override built-in aliases using set()
        $customRoot = '/custom/root/path';
        $pathAlias->set('@root', $customRoot);
        $this->assertSame($customRoot, $pathAlias->get('@root'));

        // Verify we can set it back
        $pathAlias->set('@root', $originalRoot);
        $this->assertSame($originalRoot, $pathAlias->get('@root'));
    }

    /**
     * Test that built-in aliases use direct assignment and resolve "@root/vendor" correctly.
     */
    public function testBuiltInAliasesResolveNestedReferences(): void
    {
        $pathAlias = new PathAlias();
        $root = $pathAlias->get('@root');

        // @vendor should be resolved to actual path, not "@root/vendor"
        $vendor = $pathAlias->get('@vendor');
        $this->assertStringNotContainsString('@', $vendor);
        $this->assertSame("$root/vendor", $vendor);

        // @runtime should be resolved to actual path, not "@root/runtime"
        $runtime = $pathAlias->get('@runtime');
        $this->assertStringNotContainsString('@', $runtime);
        $this->assertSame("$root/runtime", $runtime);
    }

    /**
     * Test that resolve() handles multiple template placeholders.
     */
    public function testResolveWithMultipleTemplatePlaceholders(): void
    {
        $this->pathAlias->set('@logs', '/var/logs');
        $result = $this->pathAlias->resolve('@logs/{year}/{month}/{file}.log', [
            'year' => '2024',
            'month' => '01',
            'file' => 'app',
        ]);

        $this->assertSame('/var/logs/2024/01/app.log', $result);
    }

    /**
     * Test that resolve() handles template placeholders with non-string values.
     */
    public function testResolveWithNonStringPlaceholderValues(): void
    {
        $this->pathAlias->set('@data', '/var/data');
        $result = $this->pathAlias->resolve('@data/user/{id}.json', ['id' => 12345]);

        $this->assertSame('/var/data/user/12345.json', $result);
    }

    /**
     * Test that get() returns null for non-existent alias.
     */
    public function testGetReturnsNullForNonExistentAlias(): void
    {
        $this->assertNull($this->pathAlias->get('@nonexistent'));
    }

    /**
     * Test that get() throws InvalidAliasNameException for invalid alias name.
     */
    public function testGetThrowsExceptionForInvalidAliasName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->pathAlias->get('invalid');
    }

    /**
     * Test that has() throws InvalidAliasNameException for invalid alias name.
     */
    public function testHasThrowsExceptionForInvalidAliasName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->pathAlias->has('invalid');
    }

    /**
     * Test that remove() throws InvalidAliasNameException for invalid alias name.
     */
    public function testRemoveThrowsExceptionForInvalidAliasName(): void
    {
        $this->expectException(InvalidAliasNameException::class);
        $this->pathAlias->remove('invalid');
    }

    /**
     * Test that resolve() handles relative paths.
     */
    public function testResolveWithRelativePath(): void
    {
        $result = $this->pathAlias->resolve('relative/path/to/file.php');
        $this->assertSame('relative/path/to/file.php', $result);
    }

    /**
     * Test that resolve() handles paths with Windows-style backslashes in alias reference.
     */
    public function testResolveWithWindowsStylePathInAlias(): void
    {
        $this->pathAlias->set('@root', 'C:\\Users\\myapp');
        $result = $this->pathAlias->resolve('@root/config/database.php');

        // Path normalization should convert backslashes to forward slashes
        $this->assertSame('C:/Users/myapp/config/database.php', $result);
    }

    /**
     * Test that set() updates existing alias.
     */
    public function testSetUpdatesExistingAlias(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->assertSame('/var/www/myapp', $this->pathAlias->get('@root'));

        $this->pathAlias->set('@root', '/var/www/another');
        $this->assertSame('/var/www/another', $this->pathAlias->get('@root'));
    }

    /**
     * Test that set() handles nested aliases with multiple levels.
     */
    public function testSetWithMultipleLevelNestedAliases(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->pathAlias->set('@app', '@root/app');
        $this->pathAlias->set('@config', '@app/config');

        $this->assertSame('/var/www/myapp/app/config', $this->pathAlias->get('@config'));
    }

    /**
     * Test that resolve() handles alias paths with trailing slashes.
     */
    public function testResolveWithTrailingSlashInAlias(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $result = $this->pathAlias->resolve('@root//subdir//file.php');

        // Multiple slashes should be preserved (not normalized by resolve)
        $this->assertSame('/var/www/myapp//subdir//file.php', $result);
    }

    /**
     * Test that all() returns built-in aliases after construction.
     */
    public function testAllReturnsBuiltInAliasesInitially(): void
    {
        // Create a new instance and check built-in aliases exist
        $pathAlias = new PathAlias();
        $all = $pathAlias->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('@root', $all);
        $this->assertArrayHasKey('@vendor', $all);
        $this->assertArrayHasKey('@runtime', $all);
        $this->assertArrayHasKey('@app', $all);
        $this->assertArrayHasKey('@resources', $all);
        $this->assertGreaterThanOrEqual(5, count($all));
    }

    /**
     * Test that resolve() throws exception when alias reference doesn't exist in path.
     */
    public function testResolveThrowsExceptionForNonExistentAliasInNestedPath(): void
    {
        $this->pathAlias->set('@root', '/var/www/myapp');
        $this->expectException(AliasNotFoundException::class);
        $this->pathAlias->resolve('@nonexistent/sub/path');
    }

    /**
     * Test that resolve() handles empty context array.
     */
    public function testResolveWithEmptyContext(): void
    {
        $this->pathAlias->set('@logs', '/var/logs');
        $result = $this->pathAlias->resolve('@logs/{date}.log', []);

        // Alias should be resolved, but placeholder should remain unchanged if context is empty
        $this->assertSame('/var/logs/{date}.log', $result);
    }

    /**
     * Test that constructor sets built-in aliases with correct path structure.
     */
    public function testConstructorSetsCorrectPathStructure(): void
    {
        $pathAlias = new PathAlias();
        $root = $pathAlias->get('@root');

        // Verify paths don't have trailing slashes
        $this->assertFalse(str_ends_with($root, '/'));
        $this->assertFalse(str_ends_with($pathAlias->get('@vendor'), '/'));
        $this->assertFalse(str_ends_with($pathAlias->get('@runtime'), '/'));

        // Verify paths use forward slashes
        $this->assertStringNotContainsString('\\', $root);
        $this->assertStringNotContainsString('\\', $pathAlias->get('@vendor'));
        $this->assertStringNotContainsString('\\', $pathAlias->get('@runtime'));
    }


    /**
     * Test that set() detects self-referencing alias.
     */
    public function testSetDetectsSelfReferencingAlias(): void
    {
        $this->expectException(CircularAliasException::class);
        $this->expectExceptionMessage('Circular alias dependency detected');

        // This should create a self-reference: @a -> @a
        $this->pathAlias->set('@a', '@a/path');
    }

    /**
     * Test that set() allows valid nested aliases without circular dependency.
     */
    public function testSetAllowsValidNestedAliasesWithoutCircularDependency(): void
    {
        $this->pathAlias->set('@root', '/var/www');
        $this->pathAlias->set('@app', '@root/app');
        $this->pathAlias->set('@config', '@app/config');
        $this->pathAlias->set('@cache', '@app/cache');

        // All should resolve correctly without circular dependency
        $this->assertSame('/var/www/app/config', $this->pathAlias->get('@config'));
        $this->assertSame('/var/www/app/cache', $this->pathAlias->get('@cache'));
    }

    /**
     * Test that set() throws AliasNotFoundException when referencing non-existent alias.
     */
    public function testSetThrowsExceptionWhenReferencingNonExistentAlias(): void
    {
        $this->expectException(AliasNotFoundException::class);
        $this->expectExceptionMessage('does not exist');

        $this->pathAlias->set('@cache', '@nonexistent/cache');
    }

    /**
     * Test that deeply nested aliases work correctly without circular dependency.
     */
    public function testDeeplyNestedAliasesWorkCorrectly(): void
    {
        $this->pathAlias->set('@level1', '/base');
        $this->pathAlias->set('@level2', '@level1/l2');
        $this->pathAlias->set('@level3', '@level2/l3');
        $this->pathAlias->set('@level4', '@level3/l4');
        $this->pathAlias->set('@level5', '@level4/l5');

        $this->assertSame('/base/l2/l3/l4/l5', $this->pathAlias->get('@level5'));
    }

    /**
     * Test that constructor resolves nested aliases correctly.
     */
    public function testConstructorResolvesNestedAliasesCorrectly(): void
    {
        $pathAlias = new PathAlias();

        // Built-in aliases should be fully resolved (no @ symbols)
        $vendor = $pathAlias->get('@vendor');
        $runtime = $pathAlias->get('@runtime');
        $app = $pathAlias->get('@app');

        $this->assertStringNotContainsString('@', $vendor);
        $this->assertStringNotContainsString('@', $runtime);
        $this->assertStringNotContainsString('@', $app);
    }

    /**
     * Custom aliases referencing other aliases are expanded during construction.
     */
    public function testConstructorResolvesInteropAliasChainsInjectedViaAutowiredMap(): void
    {
        $pathAlias = new class () extends PathAlias {
            public function __construct()
            {
                $this->aliases = [
                    '@staging' => '/srv/staging',
                    '@release' => '@staging/releases',
                    '@current' => '@release/current',
                ];
                parent::__construct();
            }
        };

        $this->assertSame('/srv/staging/releases/current', $pathAlias->get('@current'));
    }

    /**
     * Constructor expansion order must resolve deeper hops after their targets,
     * exercising chained {@see PathAlias::resolveRecursive()} alias-to-alias walks.
     */
    public function testConstructorResolvesMultiHopAliasPointersWithStableInsertionOrder(): void
    {
        $pathAlias = new class () extends PathAlias {
            public function __construct()
            {
                $this->aliases = [
                    '@root' => '/tmp/multi-hop',
                    '@hop1' => '@root/var',
                    '@hop2' => '@hop1/cache',
                ];
                parent::__construct();
            }
        };

        $this->assertSame('/tmp/multi-hop/var/cache', $pathAlias->get('@hop2'));
        $this->assertSame('/tmp/multi-hop/var/cache/pools', $pathAlias->resolve('@hop2/pools'));
    }

    /**
     * Constructor eagerly resolves alias targets; use reflection to keep intermediate "@..." values
     * and assert {@see PathAlias::resolveRecursive()} walks alias-to-alias indirection.
     */
    public function testResolveRecursiveChainsWhenAliasTargetStillStartsWithAt(): void
    {
        $pathAlias = new PathAlias();

        $prop = new ReflectionProperty(PathAlias::class, 'aliases');
        /** @var array<string, string> $aliases */
        $aliases = $pathAlias->all();
        $aliases['@rt_base'] = '/tmp/ref-chain';
        $aliases['@rt_mid'] = '@rt_base/nested';
        $aliases['@rt_tip'] = '@rt_mid';
        $prop->setValue($pathAlias, $aliases);

        $method = new ReflectionMethod(PathAlias::class, 'resolveRecursive');

        $resolved = $method->invoke($pathAlias, '@rt_tip/readme.md', []);

        $this->assertSame('/tmp/ref-chain/nested/readme.md', $resolved);
    }

    /**
     * Test that constructor uses pre-configured @root alias.
     */
    public function testConstructorUsesPreConfiguredRootAlias(): void
    {
        // Use reflection to set aliases before constructor runs
        $pathAlias = new class () extends PathAlias {
            public function __construct(array $preConfiguredAliases = [])
            {
                $this->aliases = $preConfiguredAliases;
                parent::__construct();
            }
        };

        $instance = new $pathAlias(['@root' => '/custom/root']);

        // @root should remain as configured
        $this->assertSame('/custom/root', $instance->get('@root'));
        $this->assertSame('/custom/root/vendor', $instance->get('@vendor'));
    }
}
