<?php

declare(strict_types=1);

namespace Switon\Core;

use Stringable;

/**
 * Immutable pair of `category` and `content`.
 *
 * @see \Switon\Core\Categorizable
 * @see \Switon\Logging\LoggerInterface
 * @see \Switon\Eventing\EventLoggerInterface
 *
 * @phpstan-consistent-constructor
 */
readonly class Categorized implements Stringable, Categorizable
{
    /**
     * @param string $category
     * @param string|Stringable $content
     */
    protected function __construct(
        protected string            $category,
        protected string|Stringable $content
    ) {
    }

    /** Create an immutable categorized value. */
    public static function of(string $category, string|Stringable $content = ''): static
    {
        return new static($category, $content);
    }

    /** Return content as string. */
    public function __toString(): string
    {
        return (string)$this->content;
    }

    /** Return category label. */
    public function getCategory(): string
    {
        return $this->category;
    }
}
