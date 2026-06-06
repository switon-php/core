<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Fixtures;

use Switon\Core\Exception;

class TestException extends Exception
{
    protected int $testStatusCode = 500;

    public function setStatusCode(int $statusCode): void
    {
        $this->testStatusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->testStatusCode;
    }

    public function setJsonForTesting(array $json): void
    {
        $this->json = $json;
    }
}
