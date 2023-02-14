<?php
namespace Unit\Errbit\Tests\Errors;

use Errbit\Errors\Error;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    
    /**
     * @dataProvider dataproviderBase
     * @param string $msg
     * @param int $line
     * @param string $file
     * @param array $trace
     *
     * @return void
     */
    public function testBase(string $msg, int $line, string $file, array $trace): void
    {
        $instance = new Error($msg, $line, $file, $trace);
        $this->assertEquals($msg, $instance->getMessage());
        $this->assertEquals($line, $instance->getLine());
        $this->assertEquals($file, $instance->getFile());
        $this->assertEquals($trace, $instance->getTrace());
    }
    
    public function dataproviderBase() : array
    {
        return [
            [
                'msg'=> 'test',
                'line'=> 123,
                'file'=>'test.php',
                'trace'=> [1=>'test.php']
            ]
        ];
    }
}
