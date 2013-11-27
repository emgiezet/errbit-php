## Full config of errbitPHP


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
    'connect_timeout'   => 3                                     // optional 
    'write_timeout'     => 3                                     // optional
    'skipped_exceptions' => array()                              // optional
  ))
  ->start();
```
