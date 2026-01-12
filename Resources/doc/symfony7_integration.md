# Symfony 7 Integration for errbit-php

The old Symfony 2 integration used `kernel.exception` event listeners with YAML service definitions. Here's the **modern Symfony 7 approach** using PHP attributes and best practices.

## 1. Create an Exception Listener

```php
// src/EventListener/ErrbitExceptionListener.php
namespace App\EventListener;

use Emgiezet\ErrbitPHP\Errbit;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final class ErrbitExceptionListener
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $host,
        private readonly string $environment,
        private readonly bool $enabled = true,
        private readonly int $port = 80,
        private readonly bool $async = true,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $exception = $event->getThrowable();

        Errbit::instance()->configure([
            'api_key'     => $this->apiKey,
            'host'        => $this->host,
            'port'        => $this->port,
            'async'       => $this->async,
            'environment' => $this->environment,
        ])->notify($exception);
    }
}
```

## 2. Configure Services (services.yaml)

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true  # Auto-registers #[AsEventListener]

    App\:
        resource: '../src/'

    App\EventListener\ErrbitExceptionListener:
        arguments:
            $apiKey: '%env(ERRBIT_API_KEY)%'
            $host: '%env(ERRBIT_HOST)%'
            $environment: '%kernel.environment%'
            $enabled: '%env(bool:ERRBIT_ENABLED)%'
            $port: '%env(int:ERRBIT_PORT)%'
            $async: true
```

## 3. Environment Variables (.env)

```dotenv
# .env
ERRBIT_API_KEY=your_api_key_here
ERRBIT_HOST=errbit.example.com
ERRBIT_PORT=80
ERRBIT_ENABLED=true
```

## 4. Alternative: Event Subscriber Approach

If you prefer an event subscriber with more control:

```php
// src/EventSubscriber/ErrbitExceptionSubscriber.php
namespace App\EventSubscriber;

use Emgiezet\ErrbitPHP\Errbit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ErrbitExceptionSubscriber implements EventSubscriberInterface
{
    private bool $initialized = false;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $host,
        private readonly string $environment,
        private readonly bool $enabled = true,
        private readonly array $ignoredExceptions = [],
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $exception = $event->getThrowable();

        // Skip ignored exception types
        foreach ($this->ignoredExceptions as $ignoredClass) {
            if ($exception instanceof $ignoredClass) {
                return;
            }
        }

        $this->initializeErrbit();
        Errbit::instance()->notify($exception);
    }

    private function initializeErrbit(): void
    {
        if ($this->initialized) {
            return;
        }

        Errbit::instance()->configure([
            'api_key'     => $this->apiKey,
            'host'        => $this->host,
            'environment' => $this->environment,
        ]);

        $this->initialized = true;
    }
}
```

## Key Changes from Symfony 2 to Symfony 7

| Symfony 2 | Symfony 7 |
|-----------|-----------|
| XML/YAML service tags | `#[AsEventListener]` PHP attribute |
| `services.yml` | `services.yaml` with `autoconfigure: true` |
| `%parameter%` syntax | `%env(VAR)%` for environment variables |
| Constructor injection via YAML | Constructor property promotion |
| No type hints | Strict typing with `readonly` properties |

## Notes

- **Autoconfigure**: With `autoconfigure: true`, Symfony automatically detects `#[AsEventListener]` attributes and registers them
- **Priority**: Use priority `0` or lower to let Symfony's error handler run first if you want the normal error page to display
- **Async mode**: The `async => true` option uses UDP for non-blocking error reporting
- **Symfony 7.4+**: Supports union types in event listener method signatures
