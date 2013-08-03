<?php
namespace Errbit\Tests\Handlers;
use Errbit\Handlers\ErrorHandlers;
use Errbit\Errbit;

use \Mockery as m;

class ErrorHandlersTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function testNoticeHandle()
    {
        
        // $service = m::mock('service');
        // $service->shouldReceive('readTemp')->times(3)->andReturn(10, 12, 14);

        $config = array(
            'api_key'=>'9fa28ccc56ed3aae882d25a9cee5695a',
            'host' => 'errbit.redexperts.net',
            'port' => '80',
            'secure' => '443',

        );
        $errbit= new Errbit($config);
        $handler = new ErrorHandlers($errbit, array('exception', 'error', array('fatal','lol','doink')));
        
        $handler->onError(E_NOTICE, 'Errbit Test: '.E_NOTICE, __FILE__, 666);
        //$this->markTestIncomplete('This test has not been implemented yet.');
    }

}