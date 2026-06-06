<?php

declare(strict_types=1);

namespace Switon\Core;

use Throwable;

use function is_string;
use function str_contains;
use function strtr;

/**
 * Base exception for framework and package exceptions.
 *
 * Supports `{key}` placeholders and stored context.
 *
 * @see \Switon\Core\Exception\RuntimeException
 * @see \Switon\Core\StopFlow
 * @see \Switon\Http\ExceptionDispatcherInterface
 * @see \Switon\Http\DefaultExceptionHandler
 * @see \Switon\Cli\ErrorHandler
 * @see \Switon\Eventing\EventLogger
 */
class Exception extends \Exception
{
    /** @var array<string, mixed> */
    protected array $context = [];
    /** @var array<string, mixed> */
    protected array $json = [];

    /**
     * Create new exception instance.
     *
     * Supports message templates with placeholder replacement and context data.
     * Placeholders use `{key}` syntax and are replaced with context values.
     *
     * @param string|Throwable $message Exception message or throwable to wrap
     * @param array<string, mixed> $context Context data for placeholders and debugging
     * @param int $code Exception code (not HTTP status code)
     * @param \Exception|null $previous Previous exception for chaining
     */
    final protected function __construct(string|Throwable $message = '', array $context = [], int $code = 0, ?\Exception $previous = null)
    {
        if ($message instanceof Throwable) {
            $this->context = $context;
            parent::__construct($message->getMessage(), $code, $message);
        } else {
            if ($context !== []) {
                $tr = [];
                $extra = [];
                foreach ($context as $k => $v) {
                    if (str_contains($message, "{{$k}}")) {
                        $tr["{{$k}}"] = is_string($v) ? $v : Json::stringify($v);
                    } else {
                        $extra[$k] = $v;
                    }
                }
                $message = strtr($message, $tr);
                $this->context = $extra;
            }

            parent::__construct($message, $code, $previous);
        }
    }

    /**
     * Create new exception instance (factory method).
     *
     * @param string|Throwable $message Exception message or throwable to wrap
     * @param array<string, mixed> $context Context data for placeholders and debugging
     * @param int $code Exception code (not HTTP status code)
     * @param \Exception|null $previous Previous exception for chaining
     *
     * @return static New exception instance
     */
    public static function of(string|Throwable $message = '', array $context = [], int $code = 0, ?\Exception $previous = null): static
    {
        return new static($message, $context, $code, $previous);
    }

    /**
     * Create and throw new exception instance (recommended).
     *
     * @param string|Throwable $message Exception message or throwable to wrap
     * @param array<string, mixed> $context Context data for placeholders and debugging
     * @param int $code Exception code (not HTTP status code)
     * @param \Exception|null $previous Previous exception for chaining
     *
     * @return never This method always throws an exception
     *
     * @throws static
     */
    public static function raise(string|Throwable $message = '', array $context = [], int $code = 0, ?\Exception $previous = null): never
    {
        throw new static($message, $context, $code, $previous);
    }

    /**
     * Get HTTP status code for this exception.
     *
     * Road-signs:
     * - statusCode not unique
     * - prefer exception class + component
     * - HTTP exit handlers map status to response shape
     *
     * @return int HTTP status code (default: 500)
     */
    public function getStatusCode(): int
    {
        return 500;
    }

    /** @return array<string, mixed> */
    public function getJson(): array
    {
        if ($this->json) {
            return $this->json;
        }
        $code = $this->getStatusCode();
        return ['code' => $code === 200 ? 0 : $code, 'msg' => $this->getMessage()];
    }

    /**
     * Get context data associated with this exception.
     *
     * @return array<string, mixed> Context data for debugging and logging
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
