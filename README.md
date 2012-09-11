# Errbit & Airbrake Client for PHP

This is a full-featured client to add integration with Errbit (or Airbrake)
to any PHP >= 5.3 application.

We had a number of issues with the
[php-airbrake-notifier](https://github.com/geoloqi/php-airbrake-notifier)
client, so we wrote this, based on the actual airbrake gem.

The php-airbrake-notifier client would regularly try to send invalid XML
to the Airbrake service and did not work at all with Errbit (the free,
self-hosted Airbrake-compatible application).

## Installation

We haven't put this in PEAR or anything like that (please feel to contribute)
so you need to install it locally.

    git clone git://github.com/flippa/errbit-php

## Usage

The intended way to use the notifier is as a singleton, though this is not
enforced and you may instantiate multiple instances if for some bizarre
reason you need to, or the word singleton makes you cry unicorn tears.

``` php
require_once 'errbit-php/lib/Errbit.php';

Errbit::instance()
  ->configure(array(
    'api_key'          => 'YOUR API KEY',
    'host'             => 'YOUR ERRBIT HOST, OR api.airbrake.io FOR AIRBRAKE',
    'port'             => 80,                                   // optional
    'secure'           => false,                                // optional
    'project_root'     => '/your/project/root',                 // optional
    'environment_name' => 'production',                         // optional
    'params_filters'   => array('/password/', '/card_number/'), // optional
    'backtrace_filters' => array('#/some/long/path#' => '')     // optional
  ))
  ->start();
```

This will install error handlers that trap your PHP errors (according to
your `error_reporting` settings) and log them to Errbit.

If you want to notify an exception manually, you can call `notify()`.

``` php
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
call `Errbit::instance()->notify($exception)`.

## Using only some of the default handlers

There are three error handlers installed by Errbit: exception, error and fatal.

By default all three are used. If you want to use your own for some handlers,
but not for others, pass the list into the `start()` method.

``` php
Errbit::instance()->start(array('error', 'fatal')); // using our own exception handler
```

## TODO

Some tests would be nice.

## License & Copyright

Copyright © Flippa.com Pty. Ltd. Licensed under the MIT license. See the LICENSE
file for details.
