# Errbit & Airbrake Client for PHP


[![Coverage Status](https://coveralls.io/repos/emgiezet/errbitPHP/badge.png)](https://coveralls.io/r/emgiezet/errbitPHP)
[![Build Status](https://travis-ci.org/emgiezet/errbitPHP.png?branch=master)](https://travis-ci.org/emgiezet/errbitPHP)
[![Dependency Status](https://www.versioneye.com/user/projects/5249e725632bac0a4900b2bf/badge.png)](https://www.versioneye.com/user/projects/5249e725632bac0a4900b2bf)
[![Latest Stable Version](https://poser.pugx.org/emgiezet/errbit-php/v/stable.png)](https://packagist.org/packages/emgiezet/errbit-php)
[![SymfonyInsight](https://insight.symfony.com/projects/a0c405fb-8ee9-40e9-acf1-eee084fc35a6/mini.svg)](https://insight.symfony.com/projects/a0c405fb-8ee9-40e9-acf1-eee084fc35a6)
[![Join the chat at https://gitter.im/emgiezet/errbitPHP](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/emgiezet/errbitPHP?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

This is a full-featured client to add integration with [Errbit](https://github.com/errbit/errbit) (or Airbrake)
to any PHP 8.0 and 8.1 application. 

Original idea and source has no support for php namespaces. 
Moreover it has a bug and with newest errbit version the xml has not supported chars.


## What is for?
Handling your errors and passing them to the Error Retention tool called [Errbit](https://github.com/errbit/errbit). It's a free alternative of sentry.io or airbrake.io.
Check the presentation below!

[![Huston whe have an Airbrake](http://image.slidesharecdn.com/hustonwehaveanairbrake-131125152637-phpapp02/95/slide-1-638.jpg?1385415083)](http://www.slideshare.net/MaxMaecki/meetphp-11-huston-we-have-an-airbrake)

## ChangeLog
Check the:

[![Full change log here](Resources/doc/changlelog.md)]
[![Releases](https://github.com/emgiezet/errbitPHP/releases)]

## Installation

### Composer Way
For php 5.3
```json
require: {
    ...
    "emgiezet/errbit-php": "1.*"
  }
```
For php 8.0+
```json
require: {
    ...
    "emgiezet/errbit-php": "2.*"
  }
```

## Usage

To setup an Errbit instance you need to configure it with an array of parameters. 
Only two of them are mandatory.

``` php
use Errbit\Errbit;

Errbit::instance()
  ->configure(array(
    'api_key'           => 'YOUR API KEY',
    'host'              => 'YOUR ERRBIT HOST, OR api.airbrake.io FOR AIRBRAKE'
  ))
  ->start();
```

View the [full configuration](https://github.com/emgiezet/errbitPHP/blob/master/Resources/doc/full_config.md).

This will register error handlers:

``` php
set_error_handler();
set_exception_handler();
register_shutdown_function();
```

And log all the errors intercepted by handlers to your errbit.

If you want to notify an exception manually, you can call `notify()` without calling a `start()`. That way you can avoid registering the handlers.

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

With this type of use. Library will not handle the errors collected by:

``` php
set_error_handler();
register_shutdown_function();
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

## Kohana 3.3 Integration

check out the [kohana-errbit](https://github.com/kwn/kohana-errbit) for kohana 3.3 integration.

## Symfony 1.4 Integration

No namespaces in php 5.2 so this library can't be used. 
Go to [filipc/sfErrbitPlugin](https://github.com/filipc/sfErrbitPlugin) and monitor your legacy 1.4 applications.



## License & Copyright

Copyright © mmx3.pl 2013 Licensed under the MIT license. Based on idea of git://github.com/flippa/errbit-php.git but rewritten in 90%.

## Contributors

https://github.com/emgiezet/errbitPHP/graphs/contributors

Rest of the contributors:
Author: [emgiezet](https://github.com/emgiezet/) 
[Contributors page](https://github.com/emgiezet/errbitPHP/graphs/contributors)
