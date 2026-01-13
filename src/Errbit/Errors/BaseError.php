<?php
declare(strict_types=1);
namespace Errbit\Errors;

use Throwable;

/**
 *
 */
abstract class BaseError extends \Exception
{
    protected string $errorFile = '';
    /** @var list<array<string, mixed>> */
    protected array $backtrace = [];

    /**
     * @param list<array<string, mixed>> $backtrace
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        string $file = '',
        array $backtrace = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorFile = $file;
        $this->backtrace = $backtrace;
        if ($file !== '') {
            $this->file = $file;
        }
    }

    public function getErrorFile(): string
    {
        return $this->errorFile;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getBacktrace(): array
    {
        return $this->backtrace;
    }
}
