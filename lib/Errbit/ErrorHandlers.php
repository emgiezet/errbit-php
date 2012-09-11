<?php

/**
 * Errbit PHP Notifier.
 *
 * Copyright © Flippa.com Pty. Ltd.
 * See the LICENSE file for details.
 */

/**
 * The default error handlers that delegate to Errbit::notify().
 *
 * You can use your own, if you prefer to do so.
 */
class Errbit_ErrorHandlers {
	/**
	 * Register all error handlers for the given $errbit client.
	 *
	 * @param [Errbit] $errbit
	 *   the client instance
	 */
	public static function register($errbit) {
		new self($errbit);
	}

	private $_errbit;

	/**
	 * Instantiate a new handler for the given client.
	 *
	 * @param [Errbit] $errbit
	 *   the client to use
	 */
	public function __construct($errbit) {
		$this->_errbit = $errbit;
		$this->_install();
	}

	// -- Handlers

	public function onError($code, $message, $file, $line) {
		switch ($code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$exception = new Errbit_Errors_Notice($message, $file, $line, debug_backtrace());
				break;

			case E_WARNING:
			case E_USER_WARNING:
				$exception = new Errbit_Errors_Warning($message, $file, $line, debug_backtrace());
				break;

			case E_ERROR:
			case E_USER_ERROR:
			default:
				$exception = new Errbit_Errors_Error($message, $file, $line, debug_backtrace());
		}

		$this->_errbit->notify($exception);
	}

	public function onException($exception) {
		$this->_errbit->notify($exception);
	}

	public function onShutdown() {
		if ($error = error_get_last()) {
			$this->_errbit->notify(new Errbit_Errors_Fatal($error['message'], $error['file'], $error['line']));
		}
	}

	// -- Private Methods

	private function _install() {
		set_error_handler(array($this, 'onError'), error_reporting());
		set_exception_handler(array($this, 'onException'));
		register_shutdown_function(array($this, 'onShutdown'));
	}
}
