<?php
declare(strict_types=1);
namespace Errbit\Errors;

class Fatal extends BaseError
{
    /**
     * Create a new fatal error wrapping the given error context info.
     */
    public function __construct(string $message, int $line, ?\Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $line,
            $previous
        );
    }
}
