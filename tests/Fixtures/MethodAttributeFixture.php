<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Fixtures;

class MethodAttributeFixture
{
    #[SampleMethodAttribute]
    public function marked(): void
    {
    }
}
