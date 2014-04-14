<?php
namespace Errbit\Tests\Errors;

use \Mockery as m;

class ErrorTest extends \PHPUnit_Framework_TestCase
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
