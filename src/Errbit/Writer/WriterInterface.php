<?php

namespace Errbit\Writer;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;

interface WriterInterface
{
    
    /**
     * @param \Errbit\Errors\Fatal|\Errbit\Errors\Warning|\Errbit\Errors\Notice|\Errbit\Errors\Error $exception
     * @param array $config
     *
     * @return mixed
     */
    public function write(Fatal|Warning|Notice|Error $exception, array $config);
}
