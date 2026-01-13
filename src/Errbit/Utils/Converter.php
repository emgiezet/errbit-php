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
     * @param list<array<string, mixed>> $backtrace
     */
    public function convert(int $code, string $message, ?\Throwable $previous = null, string $file ='', ?int $line = null, array $backtrace = []): \Throwable
    {
        return match ($code) {
            E_NOTICE, E_USER_NOTICE => new Notice($message, $line, $previous, $file, $backtrace),
            E_WARNING, E_USER_WARNING => new Warning($message, $line, $previous, $file, $backtrace),
            E_RECOVERABLE_ERROR, E_ERROR, E_CORE_ERROR => new Fatal($message, $line ?? 0, $previous, $file),
            default => new Error($message, $line, $previous, $file, $backtrace),
        };
    }
}
