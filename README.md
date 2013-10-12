# Errbit & Airbrake Client for PHP

[![Coverage Status](https://coveralls.io/repos/emgiezet/errbitPHP/badge.png)](https://coveralls.io/r/emgiezet/errbitPHP)

[![Build Status](https://travis-ci.org/emgiezet/errbitPHP.png?branch=master)](https://travis-ci.org/emgiezet/errbitPHP)

[![Dependency Status](https://www.versioneye.com/user/projects/5249e725632bac0a4900b2bf/badge.png)](https://www.versioneye.com/user/projects/5249e725632bac0a4900b2bf)

This is a full-featured client to add integration with [Errbit](https://github.com/errbit/errbit) (or Airbrake)
to any PHP >= 5.3 application. 

Original idea and source has no support for php namespaces. Moreover it has a bug and with newest errbit version the xml has not supported chars.


## Installation

We haven't put this in PEAR or anything like that (please feel to contribute)
so you need to install it locally.

### Clone Way

    git clone git://github.com/emgiezet/errbitPHP.git

### Composer Way

```json
require: {
    ...
    "emgiezet/errbit-php": "dev-master"
  }
```

## Usage

The intended way to use the notifier is as a singleton, though this is not
enforced and you may instantiate multiple instances if for some bizarre
reason you need to, or the word singleton makes you cry unicorn tears.

``` php
use Errbit\Errbit;

Errbit::instance()
  ->configure(array(
    'api_key'           => 'YOUR API KEY',
    'host'              => 'YOUR ERRBIT HOST, OR api.airbrake.io FOR AIRBRAKE',
    'port'              => 80,                                   // optional
    'secure'            => false,                                // optional
    'project_root'      => '/your/project/root',                 // optional
    'environment_name'  => 'production',                         // optional
    'params_filters'    => array('/password/', '/card_number/'), // optional
    'backtrace_filters' => array('#/some/long/path#' => '')      // optional
  ))
  ->start();
```

This will install error handlers that trap your PHP errors (according to
your `error_reporting` settings) and log them to Errbit.

If you want to notify an exception manually, you can call `notify()`.

``` php
use Errbit\Errbit;

try {
  somethingErrorProne();
} catch (Exception $e) {
  Errbit::instance()->notify(
    $e,
    array('controller'=>'UsersController', 'action'=>'show')
  );
}
```

## Using your own error handler

If you don't want Errbit to install its own error handlers and prefer to use
your own, you can just leave out the call to `start()`, then wherever you
catch an Exception (note the errors *must* be converted to Exceptions), simply
call

``` php
use Errbit\Errbit;
Errbit::instance()->notify($exception);
```

## Using only some of the default handlers

There are three error handlers installed by Errbit: exception, error and fatal.

By default all three are used. If you want to use your own for some handlers,
but not for others, pass the list into the `start()` method.

``` php
use Errbit\Errbit;
Errbit::instance()->start(array('error', 'fatal')); // using our own exception handler
```

## Symfony2 Integration

See the [documentation](https://github.com/emgiezet/errbitPHP/blob/master/Resources/doc/symfony2_integration.md) for symfony2 integration.

## Kohana3 Integration

See the [documentation](https://github.com/emgiezet/errbitPHP/blob/master/Resources/doc/kohana3_integration.md) for kohana3 integration.
## License & Copyright

Copyright © mmx3.pl 2013 Licensed under the MIT license. Based on idea of git://github.com/flippa/errbit-php.git but rewritten in 90%. See the [LICENSE](https://github.com/emgiezet/errbitPHP/blob/master/LICENSE)
file for details.

## Contributors

main idea @flippa
OOP Rework, Tests and packaging @emgiezet

Rest of the contributors:
[Contributors page](https://github.com/emgiezet/errbitPHP/graphs/contributors)
