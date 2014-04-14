<?php
namespace Errbit\Tests\Errors;

use Errbit\Errors\Fatal;
use \Mockery as m;

class FatalTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testFatal()
    {
        $object = new Fatal('test', 12, __FILE__);

        $msg = $object->getMessage();
        $this->assertEquals('test', $msg, 'Message of base error missmatch');

        $line = $object->getLine();
        $this->assertEquals(12, $line, 'Line no mismatch');

        $file = $object->getFile();
        $this->assertEquals(__FILE__, $file, 'File missmatch');

        $trace = $object->getTrace();

        $actualTrace = array(
            array(
                'line'     => 12,
                'file'     => __FILE__,
                'function' => '<unknown>'
                )
            );
        $this->assertEquals($actualTrace, $trace, 'trace missmatch');
    }
}
