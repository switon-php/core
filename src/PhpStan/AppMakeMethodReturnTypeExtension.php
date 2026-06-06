<?php

declare(strict_types=1);

namespace Switon\Core\PhpStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Switon\Core\App;

/**
 * Infers App::make() return type from the requested class-string.
 */
class AppMakeMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    use ClassNameFromTypeResolver;

    public function getClass(): string
    {
        return App::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'make';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall       $methodCall,
        Scope            $scope,
    ): ?Type {
        return $this->resolveMakeReturnTypeFromStaticCall($methodCall, $scope);
    }
}
