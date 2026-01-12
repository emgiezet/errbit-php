<?php
declare(strict_types=1);
namespace Errbit\Errors;

use Throwable;

/**
 *
 */
abstract class BaseError extends \Exception
{

    public function __construct(protected $message = "", protected $code = 0, private readonly ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
}
