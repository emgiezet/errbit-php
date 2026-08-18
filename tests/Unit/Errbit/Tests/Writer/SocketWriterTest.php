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

    // -- write() against a real loopback socket
    //
    // write() opens the socket itself, so it cannot be mocked out. These bind a server on an
    // ephemeral loopback port and point the config at it. Nothing leaves the machine.

    /**
     * @return array{0: resource, 1: int} the server and the port it bound to
     */
    private function startLocalServer(string $scheme): array
    {
        $flags = $scheme === 'udp'
            ? STREAM_SERVER_BIND
            : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;

        // A roomy receive buffer: the async path sends its fragments back to back, and a short
        // buffer is the one realistic way loopback UDP drops them.
        $context = stream_context_create(['socket' => ['so_rcvbuf' => 1048576]]);

        $server = stream_socket_server($scheme . '://127.0.0.1:0', $errno, $errstr, $flags, $context);
        $this->assertNotFalse($server, sprintf('could not bind local %s server: %s', $scheme, $errstr));

        $name = stream_socket_get_name($server, false);
        $this->assertIsString($name);
        $port = (int) substr($name, (int) strrpos($name, ':') + 1);

        return [$server, $port];
    }

    public function testWriteSendsPayloadOverTcp(): void
    {
        [$server, $port] = $this->startLocalServer('tcp');

        $config = $this->getDefaultConfig();
        $config['port'] = $port;

        $writer = new SocketWriter();
        // write() connects, writes and closes, so the payload is already buffered by the time
        // it returns and we can accept afterwards without needing a second process.
        $this->assertNull($writer->write(new Notice('socket notice', 10, null, '/test/file.php', []), $config));

        $connection = stream_socket_accept($server, 5);
        $this->assertNotFalse($connection, 'server never saw a connection');
        $received = stream_get_contents($connection);
        fclose($connection);
        fclose($server);

        $this->assertStringContainsString('POST /notifier_api/v2/notices/ HTTP/1.1', $received);
        $this->assertStringContainsString('Content-Type: text/xml', $received);
        $this->assertStringContainsString('<api-key>test-api-key</api-key>', $received);
        $this->assertStringContainsString('socket notice', $received);
    }

    public function testWriteChunksLargePayloadWhenAsync(): void
    {
        [$server, $port] = $this->startLocalServer('udp');

        $config = $this->getDefaultConfig();
        $config['port'] = $port;
        $config['async'] = true;

        // Async skips the HTTP headers, so the payload is the bare XML. Over 7000 characters
        // is what triggers the chunking branch.
        $exception = new Notice(str_repeat('E', 9000), 10, null, '/test/file.php', []);

        $writer = new SocketWriter();
        $this->assertNull($writer->write($exception, $config));

        stream_set_blocking($server, false);
        $packets = [];
        while (true) {
            $datagram = stream_socket_recvfrom($server, 65535);
            if ($datagram === false || $datagram === '') {
                break;
            }
            $packets[] = $datagram;
        }
        fclose($server);

        $this->assertGreaterThanOrEqual(2, count($packets), 'payload was not split into fragments');

        $decoded = array_map(
            static fn (string $packet): array => json_decode($packet, true, 512, JSON_THROW_ON_ERROR),
            $packets
        );

        $messageIds = array_unique(array_column($decoded, 'messageid'));
        $this->assertCount(1, $messageIds, 'fragments must share one message id');

        // Asserted without relying on arrival order, which UDP does not promise.
        $flagged = array_filter($decoded, static fn (array $packet): bool => isset($packet['last']));
        $this->assertCount(1, $flagged, 'exactly one fragment must carry the last flag');
        $this->assertTrue(reset($flagged)['last']);

        $reassembled = implode('', array_column($decoded, 'data'));
        $this->assertStringContainsString('<notice version="2.2">', $reassembled);
    }

    public function testWriteReadsResponseWhenCharactersToReadIsSet(): void
    {
        [$server, $port] = $this->startLocalServer('tcp');

        $config = $this->getDefaultConfig();
        $config['port'] = $port;
        // The server in this process cannot reply while write() is blocked in fread(), so the
        // read ends on the stream timeout. Keep it at 1s: that is the cost of this test.
        $config['write_timeout'] = 1;

        $writer = new SocketWriter();
        $writer->charactersToRead = 128;

        $this->assertNull($writer->write(new Notice('proxied', 10, null, '/test/file.php', []), $config));

        $connection = stream_socket_accept($server, 5);
        $this->assertNotFalse($connection);
        $received = stream_get_contents($connection);
        fclose($connection);
        fclose($server);

        // The payload still goes out; charactersToRead only adds a read afterwards.
        $this->assertStringContainsString('<api-key>test-api-key</api-key>', $received);
    }
}
