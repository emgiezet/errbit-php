<?php

namespace Errbit\Tests;

use Errbit\Errbit;
use Mockery;

class ErrbitTest extends \PHPUnit_Framework_TestCase
{
    protected $errbit;

    public function setUp()
    {
        $config = array(
            'api_key' => 'test',
            'host' => 'test',
            'skipped_exceptions' => array(
                'BadMethodCallException'
            )
        );
        $this->errbit = new Errbit($config);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function shouldPassExceptionsToWriter()
    {
        $exception = Mockery::mock('BadFunctionCallException');

        $writer = Mockery::mock('Errbit\Writer\WriterInterface');
        $writer->shouldReceive('write')->with($exception, Mockery::any());
        $this->errbit->setWriter($writer);

        $this->errbit->notify($exception);
    }

    /**
     * @test
     */
    public function shouldIgnoreSkippedExceptions()
    {
        $exception = Mockery::mock('BadMethodCallException');

        $writer = Mockery::mock('Errbit\Writer\WriterInterface');
        $this->errbit->setWriter($writer);

        $this->errbit->notify($exception);
    }
}
