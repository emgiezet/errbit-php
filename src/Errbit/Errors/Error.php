<?php
declare(strict_types=1);
namespace Errbit\Errors;

class Error extends BaseError
{
    /**
     * @param list<array<string, mixed>> $backtrace
     */
    public function __construct(
        string $message,
        ?int $line = null,
        ?\Throwable $previous = null,
        string $file = '',
        array $backtrace = []
    ) {
        parent::__construct($message, 0, $previous, $file, $backtrace);
        if ($line !== null) {
            $this->line = $line;
        }
    }
}
