<?php

namespace Unit\Errbit\Tests\Writer;

use Errbit\Errbit;
use Errbit\Handlers\ErrorHandlers;
use Errbit\Writer\GuzzleWriter;
use Exception;
use GuzzleHttp\Client;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GuzzleWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    
    /**
     * @dataProvider dataProviderTestWrite
     *
     */
    public function testWrite( int $error ): void
    {
        $config = [
            'host'=>'127.0.0.1',
            'port'=>'8080',
            'secure'=>false,
            'api_key'=>'fa7619c7bfe2b9725992a495eea61f0f'
        ];
        $errbit= new Errbit($config);
        $clientMock = Mockery::mock(Client::class);
        $clientMock->shouldReceive('post')->once();
        $writer = new GuzzleWriter($clientMock);
        $errbit->setWriter($writer);
        $handler = new ErrorHandlers($errbit, ['exception', 'error', ['fatal', 'lol', 'doink']]);
        $caught = [];
        try {
            $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
        } catch ( Exception $e) {
            $caught[] = $e->getMessage();
        }
        $this->assertEmpty($caught, 'Exceptions are thrown during errbit notice: '.print_r($caught,1));
    
    }
    
    public function dataProviderTestWrite(): array
    {
        return [
            [E_NOTICE],
            [E_USER_NOTICE],
            [E_WARNING],
            [E_USER_WARNING],
            [E_ERROR],
            [E_USER_ERROR]
        ];
    }
}
