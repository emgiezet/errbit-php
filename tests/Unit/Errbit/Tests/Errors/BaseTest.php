<?php
namespace Unit\Errbit\Tests\Errors;

use Errbit\Errors\Base;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{

   use MockeryPHPUnitIntegration;

    public function testBase()
    {
        $object = new Base('test', 12, __FILE__, ['trace']);

        $msg = $object->getMessage();
        $this->assertEquals('test', $msg, 'Message of base error missmatch');

        $line = $object->getLine();
        $this->assertEquals(12, $line, 'Line no mismatch');

        $file = $object->getFile();
        $this->assertEquals(__FILE__, $file, 'File missmatch');

        $trace = $object->getTrace();
        $this->assertEquals(['trace'], $trace, 'trace missmatch');
    }
}
