<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Writer;

use Errbit\Errors\Error;
use Errbit\Errors\Notice;
use Errbit\Writer\SocketWriter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SocketWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function getDefaultConfig(): array
    {
        return [
            'api_key' => 'test-api-key',
            'host' => '127.0.0.1',
            'port' => 8080,
            'secure' => false,
            'async' => false,
            'connect_timeout' => 1,
            'write_timeout' => 1,
            'agent' => 'errbitPHP',
            'project_root' => '/app',
            'environment_name' => 'test',
            'params_filters' => [],
            'backtrace_filters' => [],
        ];
    }

    public function testCharactersToReadDefaultValue(): void
    {
        $writer = new SocketWriter();
        $this->assertFalse($writer->charactersToRead);
    }

    public function testCharactersToReadCanBeSet(): void
    {
        $writer = new SocketWriter();
        $writer->charactersToRead = 1024;
        $this->assertEquals(1024, $writer->charactersToRead);
    }

    public function testBuildPayloadReturnsString(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $exception = new Notice('Test notice', 10, null, '/test/file.php', []);

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $exception, $config);

        $this->assertIsString($result);
        $this->assertStringContainsString('POST /notifier_api/v2/notices/ HTTP/1.1', $result);
        $this->assertStringContainsString('Host: 127.0.0.1', $result);
        $this->assertStringContainsString('Content-Type: text/xml', $result);
    }

    public function testBuildPayloadAsyncModeNoHeaders(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['async'] = true;
        $exception = new Notice('Test notice', 10, null, '/test/file.php', []);

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $exception, $config);

        $this->assertIsString($result);
        // In async mode, no HTTP headers should be added
        $this->assertStringNotContainsString('POST /notifier_api/v2/notices/ HTTP/1.1', $result);
        // XML notice content should be present
        $this->assertStringContainsString('<notice version="2.2">', $result);
    }

    public function testBuildConnectionSchemeTcp(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['secure'] = false;
        $config['async'] = false;

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('tcp://127.0.0.1', $result);
    }

    public function testBuildConnectionSchemeSsl(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['secure'] = true;
        $config['async'] = false;

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('ssl://127.0.0.1', $result);
    }

    public function testBuildConnectionSchemeUdp(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['async'] = true;

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('buildConnectionScheme');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $config);

        $this->assertEquals('udp://127.0.0.1', $result);
    }

    public function testAddHttpHeadersIfNeededSync(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['async'] = false;
        $body = '<xml>test</xml>';

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('addHttpHeadersIfNeeded');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $body, $config);

        $this->assertStringContainsString('POST /notifier_api/v2/notices/ HTTP/1.1', $result);
        $this->assertStringContainsString('Host: 127.0.0.1', $result);
        $this->assertStringContainsString('Content-Type: text/xml', $result);
        $this->assertStringContainsString('Connection: close', $result);
        $this->assertStringContainsString($body, $result);
    }

    public function testAddHttpHeadersIfNeededAsync(): void
    {
        $writer = new SocketWriter();
        $config = $this->getDefaultConfig();
        $config['async'] = true;
        $body = '<xml>test</xml>';

        $reflection = new \ReflectionClass($writer);
        $method = $reflection->getMethod('addHttpHeadersIfNeeded');
        $method->setAccessible(true);

        $result = $method->invoke($writer, $body, $config);

        // In async mode, body is returned as-is without headers
        $this->assertEquals($body, $result);
    }
}
