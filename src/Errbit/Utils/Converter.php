<?php
/**
 * Converts any PHP Error to ErrbitException
 * Extracted from the Errbit Error Handler, in case you want to use your own errorhandler,
 * and convert errors to Exceptions.
 * @author deathowl <csergo.balint@ustream.tv>
 */

namespace Errbit\Utils;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;

/**
 * Class Converter
 * @package Errbit\Utils
 */
class Converter
{

    public static function createDefault()
    {
        return new self();
    }

    /**
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @param string $backtrace
     *
     * @return Error|Notice|Warning
     */
    public function convert($code, $message, $file, $line, $backtrace)
    {
        switch ($code) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $exception = new Notice($message, $line, $file, $backtrace);
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $exception = new Warning($message, $line, $file, $backtrace);
                break;
            case E_RECOVERABLE_ERROR:
            case E_ERROR:
            case E_CORE_ERROR:
                $exception = new Fatal($message, $line, $file, $backtrace);
                break;
            case E_USER_ERROR:
            default:
                $exception = new Error($message, $line, $file, $backtrace);
        }
        return $exception;
    }
}
