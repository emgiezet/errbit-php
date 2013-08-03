<?php
/**
 * Errbit PHP Notifier.
 *
 * Copyright © Flippa.com Pty. Ltd.
 * See the LICENSE file for details.
 */
namespace Errbit\Errors;

/**
 * Converts a native PHP error, notice, or warning into something that
 * sort of resembles an Exception.
 *
 * If PHP's Exception class wasn't so f***ing stupid and didn't make
 * everything final, this would inherit from it, but alas...
 */
class Base
{
    private $_message;
    private $_line;
    private $_file;
    private $_trace;

    /**
     * Create a new error wrapping the given error context info.
     *
     * @param string  $message message
     * @param integer $line    line
     * @param string  $file    filename
     * @param string  $trace   stacktrace
     */
    public function __construct($message, $line, $file, $trace)
    {
        $this->_message = $message;
        $this->_line    = $line;
        $this->_file    = $file;
        $this->_trace   = $trace;
    }
    /**
     * Message getter
     *
     * @return string error message
     *
     */
    public function getMessage()
    {
        return $this->_message;
    }
    /**
     * Line getter
     *
     * @return integer the number of line
     */
    public function getLine()
    {
        return $this->_line;
    }
    /**
     * File getter
     *
     * @return string name of the file
     */
    public function getFile()
    {
        return $this->_file;
    }

    public function getTrace()
    {
        return $this->_trace;
    }
}
