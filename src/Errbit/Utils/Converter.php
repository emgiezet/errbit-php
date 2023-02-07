<?php
declare(strict_types=1);

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

    public static function createDefault(): Converter
    {
        return new self();
    }
    
    /**
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $backtrace
     *
     * @return \Errbit\Errors\Error|\Errbit\Errors\Notice|\Errbit\Errors\Warning|\Errbit\Errors\Fatal
     */
    public function convert(int $code, string $message, string $file, int $line, array $backtrace): Error|Notice|Warning|Fatal
    {
        return match ($code) {
            E_NOTICE, E_USER_NOTICE => new Notice($message, $line, $file, $backtrace),
            E_WARNING, E_USER_WARNING => new Warning($message, $line, $file, $backtrace),
            E_RECOVERABLE_ERROR, E_ERROR, E_CORE_ERROR => new Fatal($message, $line, $file),
            default => new Error($message, $line, $file, $backtrace),
        };
    }
}
