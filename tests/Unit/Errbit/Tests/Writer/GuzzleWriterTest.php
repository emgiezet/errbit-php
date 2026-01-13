<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Writer;

use Errbit\Errbit;
use Errbit\Errors\Notice;
use Errbit\Writer\GuzzleWriter;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GuzzleWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function getDefaultConfig(): array
    {
        return [
            'api_key' => 'test-api-key',
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false,
            'async' => false,
            'connect_timeout' => 3,
            'write_timeout' => 3,
            'project_root' => '/app',
            'environment_name' => 'test',
            'params_filters' => [],
            'backtrace_filters' => [],
        ];
    }

    /**
     * @dataProvider dataProviderTestWrite
     */
    public function testWrite(int $error): void
    {
        $config = [
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false,
            'api_key' => 'fa7619c7bfe2b9725992a495eea61f0f'
        ];
        $errbit = new Errbit($config);
        $clientMock = \Mockery::mock(Client::class);
        $clientMock->shouldReceive('request')
            ->once()
            ->andReturn(\Mockery::mock(ResponseInterface::class));
        $writer = new GuzzleWriter($clientMock);
        $errbit->setWriter($writer);
        $handler = new \Errbit\Handlers\ErrorHandlers($errbit, ['exception', 'error', ['fatal', 'lol', 'doink']]);
        $catched = [];
        try {
            $handler->onError($error, 'Errbit Test: ' . $error, __FILE__, 666);
        } catch (\Exception $e) {
            $catched[] = $e->getMessage();
        }
        $this->assertEmpty($catched, 'Exceptions are thrown during errbit notice: ' . print_r($catched, true));
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

    public function testAsyncWrite(): void
    {
        $config = $this->getDefaultConfig();
        $config['async'] = true;

        $exception = new Notice('Test notice', 10, null, '/test.php', []);

        $promiseMock = \Mockery::mock(PromiseInterface::class);
        $clientMock = \Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive('requestAsync')
            ->once()
            ->andReturn($promiseMock);

        $writer = new GuzzleWriter($clientMock);
        $result = $writer->write($exception, $config);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testSynchronousWrite(): void
    {
        $config = $this->getDefaultConfig();
        $config['async'] = false;

        $exception = new Notice('Test notice', 10, null, '/test.php', []);

        $responseMock = \Mockery::mock(ResponseInterface::class);
        $clientMock = \Mockery::mock(Client::class);
        $clientMock
            ->shouldReceive('request')
            ->once()
            ->andReturn($responseMock);

        $writer = new GuzzleWriter($clientMock);
        $result = $writer->write($exception, $config);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testBuildConnectionSchemeHttp(): void
    {
        $clientMock = \Mockery::mock(Client::class);
        $writer = new GuzzleWriter($clientMock);

        $config = $this->getDefaultConfig();
        $config['secure'] = false;

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('http://127.0.0.1:8080', $result);
    }

    public function testBuildConnectionSchemeHttps(): void
    {
        $clientMock = \Mockery::mock(Client::class);
        $writer = new GuzzleWriter($clientMock);

        $config = $this->getDefaultConfig();
        $config['secure'] = true;

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('https://127.0.0.1:8080', $result);
    }

    public function testBuildConnectionSchemeWithoutPort(): void
    {
        $clientMock = \Mockery::mock(Client::class);
        $writer = new GuzzleWriter($clientMock);

        $config = $this->getDefaultConfig();
        $config['secure'] = true;
        unset($config['port']);

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('https://127.0.0.1', $result);
    }
}
