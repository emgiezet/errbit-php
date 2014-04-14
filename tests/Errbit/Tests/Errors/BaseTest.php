<?php
namespace Errbit\Tests\Errors;

use Errbit\Errors\Base;
use \Mockery as m;

class BaseTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testBase()
    {
        $object = new Base('test', 12, __FILE__, 'trace');

        $msg = $object->getMessage();
        $this->assertEquals('test', $msg, 'Message of base error missmatch');

        $line = $object->getLine();
        $this->assertEquals(12, $line, 'Line no mismatch');

        $file = $object->getFile();
        $this->assertEquals(__FILE__, $file, 'File missmatch');

        $trace = $object->getTrace();
        $this->assertEquals('trace', $trace, 'trace missmatch');
    }
}
