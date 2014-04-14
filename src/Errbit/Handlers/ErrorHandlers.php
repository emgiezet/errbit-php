<?php
/**
 * Errbit PHP Notifier.
 *
 * Copyright © Flippa.com Pty. Ltd.
 * See the LICENSE file for details.
 *
 * @category   Errors
 * @package    ErrbitPHP
 * @subpackage Errbit
 * @author     Flippa <flippa@Flippa.com>
 * @author     Max Malecki <emgiezet@github.com>
 * @license    https://github.com/emgiezet/errbit-php/blob/master/LICENSE MIT
 * @link       https://github.com/emgiezet/errbit-php Repo
 */

namespace Errbit\Handlers;

use Errbit\Errbit;
use Errbit\Errors\Fatal;
use Errbit\Utils\Converter;

/**
 * The default error handlers that delegate to Errbit::notify().
 *
 * You can use your own, if you prefer to do so.
 */
class ErrorHandlers
{
    /**
     * @var Errbit
     */
    private $errbit;

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
    public static function register($errbit, $handlers = array('exception', 'error', 'fatal'))
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
    public function __construct($errbit, $handlers)
    {
        $this->errbit = $errbit;
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
    public function onError($code, $message, $file, $line)
    {
        $exception = $this->converter->convert($code, $message, $file, $line, debug_backtrace());
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
            set_error_handler(array($this, 'onError'), error_reporting());
        }

        if (in_array('exception', $handlers)) {
            set_exception_handler(array($this, 'onException'));
        }

        if (in_array('fatal', $handlers)) {
            register_shutdown_function(array($this, 'onShutdown'));
        }
    }
}
