<?php

declare(strict_types=1);

namespace Switon\Core\Exception;

/**
 * Signals failures to detect the project root from runtime metadata.
 *
 * @see \Switon\Core\Runtime::getRoot()
 * @see \Switon\Kernel\KernelInterface::start()
 */
class ProjectRootDetectionException extends RuntimeException
{
}
