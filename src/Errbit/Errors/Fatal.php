<?php
declare(strict_types=1);
namespace Errbit\Errors;

class Fatal extends BaseError
{
    /**
     * Create a new fatal error wrapping the given error context info.
     */
    public function __construct(
        string $message,
        int $line,
        ?\Throwable $previous = null,
        string $file = ''
    ) {
        parent::__construct(
            $message,
            $line,
            $previous,
            $file
        );
        if ($line > 0) {
            $this->line = $line;
        }
    }
}
