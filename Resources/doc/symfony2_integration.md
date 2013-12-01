# Symfony2 Integration

## Composer
Add to your `composer.json`

```json
require: {
    ...
    "emgiezet/errbit-php": "dev-master"
  }
```
Bring the action!

```
$composer.phar install
```

## Exception Listener

```php
<?php

namespace Acme\DemoBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Errbit\Errbit;

class ErrbitExceptionListener
{
    /**
     * @var boolean
     */
    private $enableLog;

    /**
     * Constructor
     *
     * @param array $errbitParams
     */
    public function __construct(array $errbitParams)
    {
        $this->enableLog = $errbitParams['errbit_enable_log'];
        Errbit::instance()->configure($errbitParams);
    }

    /**
     * Handle exception method
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->enableLog) {
            // get exeption and send to errbit
            Errbit::instance()->notify($event->getException());
        }
    }
}
```

## Service Definition

```yaml

services:
    acme_demo.event_listener.errbit_exception_listener:
        class: Acme\DemoBundle\EventListener\ErrbitExceptionListener
        arguments: [%errbit%]
        tags:
            - { name: kernel.event_listener, event: kernel.exception, method: onKernelException }

```

Yay We're nearly there!

## Config

### config.yml
```yaml
parameters:
    errbit:
        errbit_enable_log: %errbit_enable_log%
        api_key: %errbit_api_key%
        host: errbit.yourhosthere.com
        port: 80
        environment_name: production
        skipped_exceptions: [] # optional list of exceptions FQDN
```
### parameters.yml(.ini)

```yaml
#parameters.yml
parameters:
    errbit_enable_log : true
    errbit_api_key : yourApiKeyHere
```

```ini
; parameters.ini
[parameters]
    errbit_enable_log = true
    errbit_api_key = yourApiKeyHere
```

## If you have some problems here.

Try the full integration example at: [Symfony2 ErrbitPHP Sandbox](https://github.com/emgiezet/symfony2-errbit)