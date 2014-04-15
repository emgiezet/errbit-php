<?php
namespace Errbit\Tests\Errors;

use \Mockery as m;

class NoticeTest extends \PHPUnit_Framework_TestCase
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
