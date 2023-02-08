<?php
declare(strict_types=1);
namespace Errbit\Errors;

/**
 *
 */
abstract class BaseError
{
    /**
     * Create a new error wrapping the given error context info.
     *
     * @param string  $message message
     * @param integer $line    line
     * @param string  $file    filename
     * @param array $trace
     */
    public function __construct(private string $message, private int $line, private string $file, private array $trace)
    {
    }
    /**
     * Message getter
     *
     * @return string error message
     *
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    /**
     * Line getter
     *
     * @return integer the number of line
     */
    public function getLine(): int
    {
        return $this->line;
    }
    /**
     * File getter
     *
     * @return string name of the file
     */
    public function getFile(): string
    {
        return $this->file;
    }
    
    /**
     * @return array
     */
    public function getTrace(): array
    {
        return $this->trace;
    }
}
