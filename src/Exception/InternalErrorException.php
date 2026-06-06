<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

use Switon\Core\Exception;

/**
 * Exception for internal server errors (HTTP 500).
 *
 * @see \Switon\Http\DefaultExceptionHandler
 */
class InternalErrorException extends Exception
{
}
