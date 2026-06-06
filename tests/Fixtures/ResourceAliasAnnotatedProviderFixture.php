<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Fixtures;

use Switon\Core\Attribute\ResourceAlias;

/**
 * Lives under packages/core/tests/Fixtures so {@see \Switon\Core\ResourceAliasRegistrar} detects core layout.
 */
#[ResourceAlias(path: 'resources', alias: '@test.fixture.resources')]
class ResourceAliasAnnotatedProviderFixture
{
}
