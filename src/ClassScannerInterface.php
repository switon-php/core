<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Published discovery protocol for class scanning and bootstrap lookup.
 *
 * @see \Switon\Core\ClassScanner
 */
interface ClassScannerInterface
{
    /**
     * @param array<int|string, string> $entries Direct class names or `glob => class pattern` pairs
     * @param class-string|null $attributeClass Optional class-level attribute filter
     * @param class-string|null $baseType Optional parent class or interface filter
     *
     * @return list<class-string>
     */
    public function scan(array $entries, ?string $attributeClass = null, ?string $baseType = null): array;
}
