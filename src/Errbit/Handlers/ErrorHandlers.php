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
    private Converter $converter;
    
    
    /**
     * @param \Errbit\Errbit $errbit
     * @param array $handlers
     *
     * @return void
     */
    public static function register(Errbit $errbit, array $handlers = ['exception', 'error', 'fatal']): void
    {
        new self($errbit, $handlers);
    }

    /**
     * Instantiate a new handler for the given client.
     *
     * @param Errbit $errbit the client to use
     *
     *
     */
    public function __construct(private Errbit $errbit, $handlers)
    {
        $this->install($handlers);
        $this->converter = Converter::createDefault();
    }

    // -- Handlers
    
    /**
     * on Error
     *
     * @param integer $code error code
     * @param string $message error message
     * @param string $file error file
     * @param int $line
     *
     * @throws \Errbit\Exception\Exception
     */
    public function onError(int $code, string $message, string $file, int $line): void
    {
        $exception = $this->converter->convert($code, $message, $file,
            $line, debug_backtrace());
        $this->errbit->notify($exception);
    }
    
    /**
     * On exception
     *
     * @throws \Errbit\Exception\Exception
     */
    public function onException($exception): void
    {
        $this->errbit->notify($exception);
    }
    
    /**
     * On shut down
     *
     * @throws \Errbit\Exception\Exception
     */
    public function onShutdown(): void
    {
        if (($error = error_get_last()) && $error['type'] & error_reporting()) {
            $this->errbit->notify(new Fatal($error['message'], $error['file'], $error['line']));
        }
    }

    // -- Private Methods
    /**
     * Installer
     */
    private function install($handlers): void
    {
        if (in_array('error', $handlers, true)) {
            set_error_handler([$this, 'onError'], error_reporting());
        }

        if (in_array('exception', $handlers, true)) {
            set_exception_handler([$this, 'onException']);
        }

        if (in_array('fatal', $handlers, true)) {
            register_shutdown_function([$this, 'onShutdown']);
        }
    }
}
