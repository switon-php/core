<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Switon\Core\ClassScanner;
use Switon\Core\FilesystemInterface;
use Switon\Core\Tests\Fixtures\SampleClassAttribute;
use Switon\Core\Tests\TestCase;
use Error;

#[AllowMockObjectsWithoutExpectations]
class ClassScannerTest extends TestCase
{
    protected FilesystemInterface&MockObject $filesystem;

    protected function setUpContainer(): void
    {
        parent::setUpContainer();
        $this->container->get(\Switon\Core\PathAliasInterface::class)->set('@app', '/app');
        $this->filesystem = $this->createMock(FilesystemInterface::class);
        $this->container->replace(FilesystemInterface::class, $this->filesystem);
    }

    protected function makeScanner(): ClassScanner
    {
        return $this->container->make(ClassScanner::class);
    }

    protected function defineClass(string $className): void
    {
        if (class_exists($className)) {
            return;
        }

        $pos = strrpos($className, '\\');
        $namespace = substr($className, 0, $pos);
        $shortName = substr($className, $pos + 1);

        eval("namespace {$namespace}; class {$shortName} {}");
    }

    protected function defineSubclass(string $className, string $parentClassName): void
    {
        if (class_exists($className)) {
            return;
        }

        $pos = strrpos($className, '\\');
        $namespace = substr($className, 0, $pos);
        $shortName = substr($className, $pos + 1);

        eval("namespace {$namespace}; class {$shortName} extends \\{$parentClassName} {}");
    }

    protected function defineInterface(string $interfaceName): void
    {
        if (interface_exists($interfaceName)) {
            return;
        }

        $pos = strrpos($interfaceName, '\\');
        $namespace = substr($interfaceName, 0, $pos);
        $shortName = substr($interfaceName, $pos + 1);

        eval("namespace {$namespace}; interface {$shortName} {}");
    }

    protected function defineImplementingClass(string $className, string $interfaceName): void
    {
        if (class_exists($className)) {
            return;
        }

        $pos = strrpos($className, '\\');
        $namespace = substr($className, 0, $pos);
        $shortName = substr($className, $pos + 1);

        eval("namespace {$namespace}; class {$shortName} implements \\{$interfaceName} {}");
    }

    protected function defineClassWithAttribute(string $className, string $attributeClass): void
    {
        if (class_exists($className)) {
            return;
        }

        $pos = strrpos($className, '\\');
        $namespace = substr($className, 0, $pos);
        $shortName = substr($className, $pos + 1);

        eval("namespace {$namespace}; #[\\{$attributeClass}] class {$shortName} {}");
    }

    public function testScanGlobWithoutFilter(): void
    {
        $this->defineClass('App\\Listener\\Foo');

        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/Listener/*.php')
            ->willReturn(['/app/Listener/Foo.php']);

        $names = $this->makeScanner()->scan(['@app/Listener/*.php' => 'App\\Listener\\*']);

        $this->assertSame(['App\Listener\Foo'], $names);
    }

    public function testScanDirectClassName(): void
    {
        $this->filesystem->expects($this->never())->method('glob');

        $this->defineClass('App\\X\\Y');
        $names = $this->makeScanner()->scan(['App\\X\\Y']);

        $this->assertSame(['App\\X\\Y'], $names);
    }

    public function testScanFiltersByBaseClass(): void
    {
        $this->filesystem->expects($this->never())->method('glob');

        $this->defineClass('App\\Scan\\BaseType');
        $this->defineSubclass('App\\Scan\\ChildType', 'App\\Scan\\BaseType');
        $this->defineClass('App\\Scan\\OtherType');

        $names = $this->makeScanner()->scan(
            ['App\\Scan\\ChildType', 'App\\Scan\\OtherType'],
            null,
            'App\\Scan\\BaseType'
        );

        $this->assertSame(['App\\Scan\\ChildType'], $names);
    }

    public function testScanFiltersByBaseInterface(): void
    {
        $this->filesystem->expects($this->never())->method('glob');

        $this->defineInterface('App\\Base\\FilterContract');
        $this->defineImplementingClass('App\\Scan\\FilterType', 'App\\Base\\FilterContract');
        $this->defineClass('App\\Scan\\OtherFilterType');

        $names = $this->makeScanner()->scan(
            ['App\\Scan\\FilterType', 'App\\Scan\\OtherFilterType'],
            null,
            'App\\Base\\FilterContract'
        );

        $this->assertSame(['App\\Scan\\FilterType'], $names);
    }

    public function testFilterByClassAttribute(): void
    {
        $this->defineClass('App\\MethodAttributeBareFixture');
        $this->defineClassWithAttribute('App\\ClassAttributeMarkedFixture', SampleClassAttribute::class);

        $this->filesystem->expects($this->once())
            ->method('glob')
            ->willReturn([
                '/app/MethodAttributeBareFixture.php',
                '/app/ClassAttributeMarkedFixture.php',
            ]);

        $names = $this->makeScanner()->scan(['@app/*.php' => 'App\\*'], SampleClassAttribute::class);

        $this->assertSame(['App\\ClassAttributeMarkedFixture'], $names);
    }

    public function testMissingClassIsSkippedWithAttributeFilter(): void
    {
        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/*.php')
            ->willReturn(['/app/NoSuchClass.php']);

        $names = $this->makeScanner()->scan(['@app/*.php' => 'App\\*'], SampleClassAttribute::class);

        $this->assertSame([], $names);
    }

    public function testMissingClassIsSkippedWithoutAttributeFilter(): void
    {
        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/*.php')
            ->willReturn(['/app/NoSuchClass.php']);

        $names = $this->makeScanner()->scan(['@app/*.php' => 'App\\*']);

        $this->assertSame([], $names);
    }

    public function testDirectClassNameWithoutAttributeIsSkipped(): void
    {
        $this->filesystem->expects($this->never())->method('glob');

        $this->defineClass('App\\DirectWithoutAttribute');

        $names = $this->makeScanner()->scan(['App\\DirectWithoutAttribute'], SampleClassAttribute::class);

        $this->assertSame([], $names);
    }

    public function testScanGlobToClassPatternWithAliasPrefix(): void
    {
        $this->defineClass('App\\Events\\UserEvent');

        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/Events/*Event.php')
            ->willReturn(['/app/Events/UserEvent.php']);

        $names = $this->makeScanner()->scan([
            '@app/Events/*Event.php' => 'App\\Events\\*Event',
        ]);

        $this->assertSame(['App\\Events\\UserEvent'], $names);
    }

    public function testScanGlobToClassPatternWithoutAliasPrefix(): void
    {
        $this->defineClass('App\\Listener\\FooListener');

        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('Listener/*Listener.php')
            ->willReturn(['/app/Listener/FooListener.php']);

        $names = $this->makeScanner()->scan([
            'Listener/*Listener.php' => 'App\\Listener\\*Listener',
        ]);

        $this->assertSame(['App\\Listener\\FooListener'], $names);
    }

    public function testScanGlobToClassPatternSkipsFileWhenWildcardCountMismatch(): void
    {
        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/Events/*Event.php')
            ->willReturn(['/app/Events/UserEvent.php']);

        $names = $this->makeScanner()->scan([
            '@app/Events/*Event.php' => 'App\\Events\\*\\*Event',
        ]);

        $this->assertSame([], $names);
    }

    public function testLegacyGlobYieldSkippedWhenGlobbedPathDoesNotMatchPattern(): void
    {
        $this->filesystem->expects($this->once())
            ->method('glob')
            ->willReturn(['/app/Event.php']);

        $names = $this->makeScanner()->scan([
            '@app/Events/*Event.php' => 'App\\Events\\*Event',
        ]);

        $this->assertSame([], $names);
    }

    public function testScanGlobToClassPatternRequiresAnchoredFullPathMatch(): void
    {
        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/Events/*Event.php')
            ->willReturn(['/app/Events/UserEvent.php.bak']);

        $names = $this->makeScanner()->scan([
            '@app/Events/*Event.php' => 'App\\Events\\*Event',
        ]);

        $this->assertSame([], $names);
    }

    public function testScanGlobToClassPatternMatchesPathsWithRegexMetacharactersLiterally(): void
    {
        $this->defineClass('App\\Modules\\Billing\\UserEvent');

        $this->filesystem->expects($this->once())
            ->method('glob')
            ->with('@app/Modules/(Billing)+[v2]/*Event.php')
            ->willReturn(['/app/Modules/(Billing)+[v2]/UserEvent.php']);

        $names = $this->makeScanner()->scan([
            '@app/Modules/(Billing)+[v2]/*Event.php' => 'App\\Modules\\Billing\\*Event',
        ]);

        $this->assertSame(['App\\Modules\\Billing\\UserEvent'], $names);
    }

    public function testScanWrapsThrowableFromAutoloadIntoReflectionFailureEvent(): void
    {
        $this->filesystem->expects($this->never())->method('glob');

        $class = 'SwitonCoreScanExplodingFixture\\Bomb' . str_replace('.', '', uniqid('', true));

        spl_autoload_register($loader = static function (string $candidate) use ($class): void {
            if ($candidate !== $class) {
                return;
            }
            throw new Error('autoload throwable');
        }, true);

        try {
            $names = $this->makeScanner()->scan([$class]);

            $this->assertSame([], $names);
        } finally {
            spl_autoload_unregister($loader);
        }
    }
}
