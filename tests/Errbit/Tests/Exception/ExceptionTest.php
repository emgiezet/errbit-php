<?php
namespace Errbit\Tests\Exception;

use \Mockery as m;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testBase()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
