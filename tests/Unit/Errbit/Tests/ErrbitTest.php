<?php

namespace Unit\Errbit\Tests;

use Errbit\Errbit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ErrbitTest extends TestCase
{
    protected $errbit;
    use MockeryPHPUnitIntegration;
    public function setUp(): void
    {
        $config = ['api_key' => 'test', 'host' => 'test', 'skipped_exceptions' => ['BadMethodCallException']];
        $this->errbit = new Errbit($config);
    }

    /**
     * @test
     */
    public function shouldPassExceptionsToWriter()
    {
        $exception = Mockery::mock('BadFunctionCallException');

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write')->with($exception, Mockery::any());
        $this->errbit->setWriter($writer);

        $return = $this->errbit->notify($exception);
        $this->assertIsObject($return);
    }

    /**
     * @test
     */
    public function shouldIgnoreSkippedExceptions()
    {
        $exception = Mockery::mock('BadMethodCallException');
        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $this->errbit->setWriter($writer);
        $return = $this->errbit->notify($exception);
        $this->assertIsObject($return);
    }
}
