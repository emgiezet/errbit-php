<?php
namespace Unit\Errbit\Tests\Exception;

use Errbit\Exception\Exception;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
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
    public function testBase(string $msg, int $code, ?\Throwable $prev): void
    {
        $instance = new Exception($msg, $code, $prev );
        $this->assertEquals($msg, $instance->getMessage());
        $this->assertEquals($code, $instance->getCode());
        $this->assertEquals($prev, $instance->getPrevious());
    }
    
    public function dataproviderBase() : array
    {
        return [
            [
                'msg'=> 'test',
                'code'=> 123,
                'prev'=>null
            ]
        ];
    }
}
