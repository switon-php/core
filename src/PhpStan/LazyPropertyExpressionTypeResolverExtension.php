<?php

declare(strict_types=1);

namespace Switon\Core\PhpStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;

/**
 * Narrows #[Autowired] Type|Lazy property reads to Type for analysis.
 *
 * Complements {@see LazyMethodsClassReflectionExtension} (method calls on Lazy marker)
 * by stripping Lazy from property fetch result types.
 *
 * @see \Switon\Core\Lazy
 * @see \Switon\Di\LazyPropertyProxy
 */
class LazyPropertyExpressionTypeResolverExtension implements ExpressionTypeResolverExtension
{
    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (!$expr instanceof PropertyFetch || !$expr->name instanceof Identifier) {
            return null;
        }

        $varType = $scope->getType($expr->var);
        if (!$varType->isObject()->yes()) {
            return null;
        }

        $propertyName = $expr->name->toString();
        foreach ($varType->getObjectClassReflections() as $classReflection) {
            $readableType = $this->getNativeReadableType($classReflection, $propertyName);
            if ($readableType === null || !LazyUnionTypeHelper::containsLazy($readableType)) {
                continue;
            }

            return LazyUnionTypeHelper::withoutLazy($readableType);
        }

        return null;
    }

    protected function getNativeReadableType(ClassReflection $classReflection, string $propertyName): ?Type
    {
        if (!$classReflection->hasNativeProperty($propertyName)) {
            return null;
        }

        return $classReflection->getNativeProperty($propertyName)->getReadableType();
    }
}
