<?php

namespace Errbit\Writer;

interface WriterInterface
{
    /**
     * @param mixed $exception exception to pass to the Errbit API
     * @param array $config configuration for Errbit API communication
     */
    public function write($exception, array $config);
}
