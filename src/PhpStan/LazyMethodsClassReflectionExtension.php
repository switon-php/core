<?php

declare(strict_types=1);

namespace Switon\Core\PhpStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Switon\Core\Lazy;

class LazyMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === Lazy::class;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new LazyMethodReflection($classReflection, $methodName);
    }
}
