<?php
namespace Errbit\Tests\Handlers;

use \Mockery as m;

class ErrorHandlersTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testBase()
    {
        // WIP
        $service = m::mock('service');
        $service->shouldReceive('readTemp')->times(3)->andReturn(10, 12, 14);
        $temperature = new Temperature($service);
        $this->assertEquals(12, $temperature->average());
    }

}