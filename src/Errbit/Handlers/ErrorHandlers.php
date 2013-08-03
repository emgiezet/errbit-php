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

use Errbit\Errors\Warning;
use Errbit\Errors\Notice;
use Errbit\Errors\Error;
use Errbit\Errors\Fatal;

/**
 * The default error handlers that delegate to Errbit::notify().
 *
 * You can use your own, if you prefer to do so.
 */
class ErrorHandlers
{
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

    private $_errbit;

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
        $this->_errbit = $errbit;
        $this->_install($handlers);
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
        switch ($code) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $exception = new Notice($message, $file, $line, debug_backtrace());
            break;

        case E_WARNING:
        case E_USER_WARNING:
            $exception = new Warning($message, $file, $line, debug_backtrace());
            break;

        case E_ERROR:
        case E_USER_ERROR:
        default:
            $exception = new Error($message, $file, $line, debug_backtrace());
        }

        $this->_errbit->notify($exception);
    }
    /**
     * On exception
     *
     *
     */
    public function onException($exception)
    {
        $this->_errbit->notify($exception);
    }
    /**
     * On shut down
     *
     *
     */
    public function onShutdown()
    {
        if (($error = error_get_last()) && $error['type'] & error_reporting()) {
            $this->_errbit->notify(new Fatal($error['message'], $error['file'], $error['line']));
        }
    }

    // -- Private Methods
    /**
     * Installer
     *
     *
     */
    private function _install($handlers)
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
