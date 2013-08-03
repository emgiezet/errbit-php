<?php
namespace Errbit\Tests;
use Errbit\Errbit;
use \Mockery as m;

class ErrbitTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testBase()
    {
        
        $service = m::mock('service');
        $service->shouldReceive('readTemp')->times(3)->andReturn(10, 12, 14);

        // $handler = new ErrorHanlers();
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}