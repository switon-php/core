<?php

declare(strict_types=1);

namespace Switon\Core\PhpStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

use function class_exists;
use function interface_exists;
use function is_string;

/**
 * Resolves make()/lookup argument types from class-string literals, class-string&lt;T&gt;, or caller templates.
 */
trait ClassNameFromTypeResolver
{
    private function resolveMakeReturnTypeFromMethodCall(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (!isset($args[0])) {
            return null;
        }

        return $this->resolveMakeReturnTypeFromArgument($args[0]->value, $scope);
    }

    private function resolveMakeReturnTypeFromStaticCall(StaticCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        if (!isset($args[0])) {
            return null;
        }

        return $this->resolveMakeReturnTypeFromArgument($args[0]->value, $scope);
    }

    private function resolveMakeReturnTypeFromArgument(Expr $argument, Scope $scope): ?Type
    {
        $resolved = $this->resolveClassNameType($scope->getType($argument));
        return $resolved ?? $this->resolveTemplateTypeFromCallingScope($argument, $scope);
    }

    private function resolveClassNameType(Type $type): ?Type
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $inner) {
                $resolved = $this->resolveClassNameType($inner);
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            return null;
        }

        $normalized = $this->normalizeResolvableObjectType($type->getClassStringObjectType());
        if ($normalized !== null) {
            return $normalized;
        }

        $objectTypeOrClassString = $type->getObjectTypeOrClassStringObjectType();
        $normalized = $this->normalizeResolvableObjectType($objectTypeOrClassString);
        if ($normalized !== null) {
            return $normalized;
        }

        $classNames = $type->getObjectClassNames();
        if ($classNames !== []) {
            $className = $classNames[0];
            if (class_exists($className) || interface_exists($className)) {
                return new ObjectType($className);
            }

            return null;
        }

        $strings = $type->getConstantStrings();
        if ($strings === []) {
            return null;
        }

        $className = $strings[0]->getValue();
        if (!class_exists($className) && !interface_exists($className)) {
            return null;
        }

        return new ObjectType($className);
    }

    private function normalizeResolvableObjectType(Type $type): ?Type
    {
        if (!$type->isObject()->yes()) {
            return null;
        }

        $classNames = $type->getObjectClassNames();
        if ($classNames !== []) {
            $className = $classNames[0];
            if (!class_exists($className) && !interface_exists($className)) {
                return null;
            }

            $classReflection = (new ObjectType($className))->getClassReflection();
            if ($classReflection === null || $classReflection->isTrait()) {
                return null;
            }

            return new ObjectType($className);
        }

        return $type;
    }

    private function resolveTemplateTypeFromCallingScope(Expr $argument, Scope $scope): ?Type
    {
        if (!$argument instanceof Variable || !is_string($argument->name)) {
            return null;
        }

        $function = $scope->getFunction();
        if (!$function instanceof FunctionReflection) {
            return null;
        }

        $templateType = $function->getTemplateTypeMap()->getType('T');
        if ($templateType === null) {
            return null;
        }

        foreach ($function->getVariants() as $variant) {
            $parameters = $variant->getParameters();
            if (!isset($parameters[0]) || $parameters[0]->getName() !== $argument->name) {
                continue;
            }

            return $templateType;
        }

        return null;
    }
}
