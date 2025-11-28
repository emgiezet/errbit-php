<?php

namespace Errbit\Writer;

use Errbit\Errors\ErrorInterface;
use Throwable;

interface WriterInterface
{
    /**
     * @param Throwable $exception
     * @param array $config
     *
     * @return mixed
     */
    public function write(Throwable|ErrorInterface $exception, array $config);
}
