<?php
declare(strict_types=1);
namespace Integration\Errbit\Tests;

use Errbit\Errbit;
use Errbit\Writer\GuzzleWriter;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    
    /**
     * @dataProvider dataProviderErrorTypes
     * @param $error
     *
     * @return void
     */
    public function testIntegration($error)
    {
        $config = [
            'host'=>'127.0.0.1',
            'port'=>'8080',
            'secure'=>false,
            'api_key'=>'fa7619c7bfe2b9725992a495eea61f0f'
        ];
        $errbit= new Errbit($config);
        $handler = new \Errbit\Handlers\ErrorHandlers($errbit, ['exception', 'error', ['fatal', 'lol', 'doink']]);
        $caught = [];
        try {
            $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
        } catch ( \Exception $e) {
            $caught[] = $e->getMessage();
        }
        $this->assertEmpty($caught, 'Exceptions are thrown during errbit notice: '.print_r($caught,true));
        
    }
    
    /**
     * @dataProvider dataProviderErrorTypes
     *
     * @return void
     */
    public function testGuzzleWriterIntegrationTest(int $error)
    {
        $config = [
            'host'=>'127.0.0.1',
            'port'=>'8080',
            'secure'=>false,
            'api_key'=>'fa7619c7bfe2b9725992a495eea61f0f'
        ];
        $errbit= new Errbit($config);
        $client = new Client(['base_uri'=>$config['host']]);
        $writer = new GuzzleWriter($client);
        $errbit->setWriter($writer);
        $handler = new \Errbit\Handlers\ErrorHandlers($errbit, ['exception', 'error', ['fatal', 'lol', 'doink']]);
        $caught = [];
        try {
            $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
        } catch ( \Exception $e) {
            $caught[] = $e->getMessage();
        }
        $this->assertEmpty($caught, 'Exceptions are thrown during errbit notice: '.print_r($caught,true));
    
    }
    
    /**
     * @dataProvider dataProviderErrorTypes
     *
     * @return void
     */
    public function testGuzzleWriterAsyncIntegrationTest(int $error)
    {
        $config = [
            'host'=>'127.0.0.1',
            'port'=>'8080',
            'secure'=>false,
            'api_key'=>'fa7619c7bfe2b9725992a495eea61f0f',
            'async'=>true
        ];
        $errbit= new Errbit($config);
        $client = new Client(['base_uri'=>$config['host']]);
        $writer = new GuzzleWriter($client);
        $errbit->setWriter($writer);
        $handler = new \Errbit\Handlers\ErrorHandlers($errbit, ['exception', 'error', ['fatal', 'lol', 'doink']]);
        $caught = [];
        try {
            $handler->onError($error, 'Errbit Test: '.$error, __FILE__, 666);
        } catch ( \Exception $e) {
            $caught[] = $e->getMessage();
        }
        $this->assertEmpty($caught, 'Exceptions are thrown during errbit notice: '.print_r($caught,true));
        
    }
    
    public function dataProviderErrorTypes(): array
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
