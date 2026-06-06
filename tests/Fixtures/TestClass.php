<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Fixtures;

class TestClass
{
    public string $name = 'default';
    public int $value = 0;

    public function __construct(string $name = 'default', int $value = 0)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
