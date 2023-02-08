<?php
declare(strict_types=1);
namespace Errbit\Errors;

class Fatal extends BaseError implements ErrorInterface
{
    /**
     * Create a new fatal error wrapping the given error context info.
     */
    public function __construct(string $message, int $line, string $file)
    {
        parent::__construct(
            $message,
            $line,
            $file,
            [['line'     => $line, 'file'     => $file, 'function' => '<unknown>']]
        );
    }
}
