# Switon Core Package

[![CI](https://img.shields.io/github/actions/workflow/status/switon-php/core/ci.yml?branch=main&label=CI)](https://github.com/switon-php/core/actions/workflows/ci.yml) [![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4)](https://www.php.net/)

Switon's shared foundation for autowiring, app metadata, runtime state, context-aware components, and JSON helpers.

## Highlights

- **Property autowiring:** `#[Autowired]` injects services, scalars, and arrays from the container.
- **App and runtime state:** app identity, environment, debug mode, timezone, and project-root detection stay explicit.
- **Scoped state support:** `ContextAware`, `ContextIsolated`, and related helpers keep mutable state controlled.
- **Common helpers:** JSON, strings, random, clock, and path helpers are included.
- **Shared contracts:** the package exposes the base interfaces other components build on.

## Installation

```bash
composer require switon/core
```

## Quick Start

```php
use Switon\Core\AppInterface;
use Switon\Core\Attribute\Autowired;
use Switon\Core\Json;

class StatusReporter
{
    #[Autowired] protected AppInterface $app;

    public function snapshot(): string
    {
        return Json::stringify([
            'app' => $this->app->name(),
            'env' => $this->app->env(),
            'debug' => $this->app->isDebug(),
        ], JSON_PRETTY_PRINT);
    }
}
```

Docs: https://docs.switon.dev/latest/core

## License

MIT.
