<?php
namespace Errbit\Tests\Utils;

use \Mockery as m;

class XmlBuilderTest extends PHPUnit_Framework_TestCase
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
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}