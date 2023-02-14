<?php
declare(strict_types=1);
namespace Unit\Errbit\Tests\Handlers;

use Errbit\Errbit;
use Errbit\Handlers\ErrorHandlers;
use Errbit\Writer\SocketWriter;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ErrorHandlersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNoticeHandle(): void
    {

        // $service = m::mock('service');
        // $service->shouldReceive('readTemp')->times(3)->andReturn(10, 12, 14);

        $config = ['api_key'=>'9fa28ccc56ed3aae882d25a9cee5695a', 'host'=>'127.0.0.1', 'port' => '8080', 'secure' => false];
        $errbit= new Errbit($config);
        
        $writerMock = Mockery::mock(SocketWriter::class);
        $writerMock->shouldReceive('write');
        $errbit->setWriter($writerMock);
        $handler = new ErrorHandlers($errbit, ['exception', 'error', 'fatal', 'lol', 'doink']);

        $errors = [E_NOTICE, E_USER_NOTICE, E_WARNING, E_USER_WARNING, E_ERROR, E_USER_ERROR];
        $caught = [];
        try {
            foreach ($errors as $error) {
                $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
            }
        } catch ( Exception $e) {
            $caught[] = $e->getMessage();
        }
        $this->assertEmpty($caught, 'Exceptions are thrown during errbit notice');
    }
}
