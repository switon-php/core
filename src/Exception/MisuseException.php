<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals API or component misuse.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Http\ExceptionDispatcherInterface
 * @see \Switon\Http\DefaultExceptionHandler
 */
class MisuseException extends RuntimeException
{
}
