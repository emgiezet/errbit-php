<?php
declare(strict_types=1);

namespace Errbit\Handlers;

use Errbit\Errbit;
use Errbit\Errors\Fatal;
use Errbit\Utils\Converter;

class ErrorHandlers
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * Register all error handlers for the given $errbit client.
     *
     * @param [Errbit] $errbit   the client instance
     * @param [Array]  $handlers an array of handler names, instead of registering all
     *
     * @return self
     */
    public static function register($errbit, $handlers = ['exception', 'error', 'fatal'])
    {
        new self($errbit, $handlers);
    }

    /**
     * Instantiate a new handler for the given client.
     *
     * @param Errbit $errbit the client to use
     *
     * @return null
     *
     */
    public function __construct(private $errbit, $handlers)
    {
        $this->install($handlers);
        $this->converter = Converter::createDefault();
    }

    // -- Handlers
    /**
     * on Error
     *
     * @param integer $code    error code
     * @param string  $message error message
     * @param string  $file    error file
     * @param string  $line    line of error
     */
    public function onError(int $code, string $message, string $file, int $line)
    {
        $exception = $this->converter->convert($code, $message, $file, (int)$line, debug_backtrace());
        $this->errbit->notify($exception);
    }
    /**
     * On exception
     *
     *
     */
    public function onException($exception)
    {
        $this->errbit->notify($exception);
    }
    /**
     * On shut down
     *
     *
     */
    public function onShutdown()
    {
        if (($error = error_get_last()) && $error['type'] & error_reporting()) {
            $this->errbit->notify(new Fatal($error['message'], $error['file'], $error['line']));
        }
    }

    // -- Private Methods
    /**
     * Installer
     *
     *
     */
    private function install($handlers)
    {
        if (in_array('error', $handlers)) {
            set_error_handler($this->onError(...), error_reporting());
        }

        if (in_array('exception', $handlers)) {
            set_exception_handler($this->onException(...));
        }

        if (in_array('fatal', $handlers)) {
            register_shutdown_function([$this, 'onShutdown']);
        }
    }
}
