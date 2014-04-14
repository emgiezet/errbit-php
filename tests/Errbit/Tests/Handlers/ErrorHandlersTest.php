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
            'secure' => false,

        );
        $errbit= new Errbit($config);
        $handler = new ErrorHandlers($errbit, array('exception', 'error', array('fatal','lol','doink')));

        $errors = array(E_NOTICE, E_USER_NOTICE, E_WARNING, E_USER_WARNING, E_ERROR, E_USER_ERROR );
        $catched = array();
        try {
            foreach ($errors as $error) {
                $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
            }
        } catch ( \Exception $e) {
            $catched[] = $e->getMessage();
        }
        $this->assertTrue(count($catched) === 0, 'Exceptions are thrown during errbit notice');
    }
}
