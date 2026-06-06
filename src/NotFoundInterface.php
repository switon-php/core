<?php

declare(strict_types=1);

namespace Switon\Core;

/**
 * Marker interface for “not found” conditions that map to HTTP 404.
 *
 * @see \Switon\Http\ExceptionDispatcherInterface
 * @see \Switon\Http\Exception\NotFoundException
 * @see \Switon\Orm\Exception\EntityNotFoundException
 */
interface NotFoundInterface
{
}
