<?php

namespace Errbit\Writer;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

interface WriterInterface
{
    
    /**
     * @param \Errbit\Errors\Fatal|\Errbit\Errors\Warning|\Errbit\Errors\Notice|\Errbit\Errors\Error $exception
     * @param array $config
     *
     * @return \Psr\Http\Message\ResponseInterface|\GuzzleHttp\Promise\PromiseInterface|null
     */
    public function write(Fatal|Warning|Notice|Error $exception, array $config):  null|ResponseInterface|PromiseInterface;
}
