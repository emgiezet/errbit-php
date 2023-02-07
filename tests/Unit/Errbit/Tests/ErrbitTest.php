<?php

namespace Unit\Errbit\Tests;

use Errbit\Errbit;
use Errbit\Errors\Error;
use Errbit\Errors\Notice;
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
        $exception = Mockery::mock(Error::class);

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
        $this->errbit->configure(['skipped_exceptions'=>[Notice::class]]);
        $exception = new Notice('Notice test', 123,'test.php', ['test']);
        //don't write because this Notice should be ignored
        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class)->shouldNotReceive('write')->getMock();
        $this->errbit->setWriter($writer);
        $return = $this->errbit->notify($exception, []);
        $this->assertIsObject($return);
    }
}
