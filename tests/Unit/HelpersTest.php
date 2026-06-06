<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Tests\Fixtures\TestClass;
use Switon\Core\Tests\TestCase;

use function fclose;
use function proc_close;
use function proc_open;
use function stream_get_contents;

class HelpersTest extends TestCase
{
    /** @var list<string> */
    private array $envKeysToUnset = [];

    protected function tearDown(): void
    {
        foreach ($this->envKeysToUnset as $key) {
            putenv($key);
        }
        $this->envKeysToUnset = [];

        parent::tearDown();
    }

    public function testArrayFirstReturnsNullForEmptyArray(): void
    {
        $this->assertNull(array_first([]));
    }

    public function testArrayFirstReturnsFirstValueWithoutNumericKeys(): void
    {
        $this->assertSame('first', array_first(['a' => 'first', 'b' => 'second']));
    }

    public function testArrayLastReturnsNullForEmptyArray(): void
    {
        $this->assertNull(array_last([]));
    }

    public function testArrayLastReturnsLastValueWithoutNumericKeys(): void
    {
        $this->assertSame('second', array_last(['a' => 'first', 'b' => 'second']));
    }

    public function testEnvReturnsDefaultWhenUnset(): void
    {
        $this->unsetEnv('SWITON_CORE_TEST_ENV');

        $this->assertSame('fallback', env('SWITON_CORE_TEST_ENV', 'fallback'));
    }

    public function testEnvReturnsRawStringWhenDefaultIsNull(): void
    {
        $this->setEnv('SWITON_CORE_TEST_ENV', 'plain');

        $this->assertSame('plain', env('SWITON_CORE_TEST_ENV'));
    }

    public function testEnvCastsToBoolUsingDefaultType(): void
    {
        $this->setEnv('SWITON_CORE_TEST_ENV', 'true');
        $this->assertTrue(env('SWITON_CORE_TEST_ENV', false));

        $this->setEnv('SWITON_CORE_TEST_ENV', 'off');
        $this->assertFalse(env('SWITON_CORE_TEST_ENV', true));
    }

    public function testEnvCastsToIntAndFloatUsingDefaultType(): void
    {
        $this->setEnv('SWITON_CORE_TEST_ENV', '42');
        $this->assertSame(42, env('SWITON_CORE_TEST_ENV', 0));

        $this->setEnv('SWITON_CORE_TEST_ENV', '3.14');
        $this->assertSame(3.14, env('SWITON_CORE_TEST_ENV', 0.0));
    }

    public function testMakeDelegatesToAppContainer(): void
    {
        $instance = make(TestClass::class, ['name' => 'helper', 'value' => 7]);

        $this->assertInstanceOf(TestClass::class, $instance);
        $this->assertSame('helper', $instance->name);
        $this->assertSame(7, $instance->value);
    }

    public function testConsoleLogWritesFormattedLineToStderr(): void
    {
        $autoload = $this->resolveAutoloadPath();
        $code = sprintf(
            'require %s; console_log(%s, %s, %s);',
            var_export($autoload, true),
            var_export('info', true),
            var_export('hello {name}', true),
            var_export(['name' => 'world'], true),
        );

        $stderr = $this->runPhpSnippet($code);

        $this->assertStringContainsString('[info]: hello world', $stderr);
    }

    private function resolveAutoloadPath(): string
    {
        $packageVendor = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (is_file($packageVendor)) {
            return $packageVendor;
        }

        return dirname(__DIR__, 4) . '/vendor/autoload.php';
    }

    private function runPhpSnippet(string $code): string
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, '-r', $code], $descriptorSpec, $pipes);
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stderr;
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("$key=$value");
        $this->envKeysToUnset[] = $key;
    }

    private function unsetEnv(string $key): void
    {
        putenv($key);
        $this->envKeysToUnset[] = $key;
    }
}
