<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\PathAliasInterface;
use Switon\Core\ResourceAliasRegistrar;
use Switon\Core\Tests\Fixtures\ResourceAliasAnnotatedProviderFixture;
use Switon\Core\Tests\Fixtures\ResourceAliasAutoAliasFixture;
use Switon\Core\Tests\TestCase;

use function bin2hex;
use function file_put_contents;
use function mkdir;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

final class ResourceAliasRegistrarTest extends TestCase
{
    private function stubPathAlias(): object
    {
        return new class () implements PathAliasInterface {
            /** @var array<string, string> */
            private array $map = [];

            public function resolve(string $path, array $context = []): string
            {
                return $path;
            }

            public function set(string $name, string $path): string
            {
                $this->map[$name] = $path;

                return $path;
            }

            public function get(string $name): ?string
            {
                return $this->map[$name] ?? null;
            }

            public function has(string $name): bool
            {
                return isset($this->map[$name]);
            }

            public function remove(string $name): void
            {
                unset($this->map[$name]);
            }

            /** @return array<string, string> */
            public function all(): array
            {
                return $this->map;
            }
        };
    }

    public function testRegisterSetsAliasWhenPackageLayoutMatches(): void
    {
        /** @var PathAliasInterface $pathAlias */
        $pathAlias = $this->stubPathAlias();

        (new ResourceAliasRegistrar())->register($pathAlias, ResourceAliasAnnotatedProviderFixture::class);

        $root = dirname(__DIR__, 2);
        $this->assertTrue($pathAlias->has('@test.fixture.resources'));
        $this->assertSame($root . '/resources', $pathAlias->get('@test.fixture.resources'));
    }

    public function testRegisterComputesVendorAliasWhenAliasAttributeLeftEmpty(): void
    {
        /** @var PathAliasInterface $pathAlias */
        $pathAlias = $this->stubPathAlias();

        (new ResourceAliasRegistrar())->register($pathAlias, ResourceAliasAutoAliasFixture::class);

        $coreRoot = dirname(__DIR__, 2);
        $this->assertTrue($pathAlias->has('@switon.core.validators'));
        $this->assertSame($coreRoot . '/validators', $pathAlias->get('@switon.core.validators'));
    }

    public function testRegisterSkipsPathsThatDoNotMatchMonorepoOrVendorPattern(): void
    {
        /** @var PathAliasInterface $pathAlias */
        $pathAlias = $this->stubPathAlias();

        $suffix = bin2hex(random_bytes(6));
        $namespace = 'Switon\\Core\\Tests\\Support\\TmpProvider' . $suffix;
        $dir = sys_get_temp_dir() . '/provider-resource-' . $suffix;
        mkdir($dir, 0777, true);
        $definition = "<?php declare(strict_types=1);\n"
            . "namespace {$namespace};\n\n"
            . "use Switon\\Core\\Attribute\\ResourceAlias;\n\n"
            . '#[ResourceAlias(alias: "@tmp.provider.unlayout")]' . "\n"
            . "final class OutsiderProvider {}\n";
        $file = $dir . '/outsider-provider.php';
        file_put_contents($file, $definition);
        try {
            require_once $file;
            /** @var class-string $fqn */
            $fqn = "{$namespace}\\OutsiderProvider";
            (new ResourceAliasRegistrar())->register($pathAlias, $fqn);
        } finally {
            @unlink($file);
            @rmdir($dir);
        }

        $this->assertFalse($pathAlias->has('@tmp.provider.unlayout'));
    }

    public function testRegisterLeavesExistingAliasesUnchanged(): void
    {
        /** @var PathAliasInterface $pathAlias */
        $pathAlias = $this->stubPathAlias();
        $existing = '/already/set';
        $pathAlias->set('@test.fixture.resources', $existing);

        (new ResourceAliasRegistrar())->register($pathAlias, ResourceAliasAnnotatedProviderFixture::class);

        $this->assertSame($existing, $pathAlias->get('@test.fixture.resources'));
    }
}
