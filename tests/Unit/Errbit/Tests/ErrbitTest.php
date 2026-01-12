<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests;

use Errbit\Errbit;
use Errbit\Errors\Error;
use Errbit\Errors\Notice;
use Errbit\Exception\ConfigurationException;
use Errbit\Exception\Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ErrbitTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected Errbit $errbit;

    public function setUp(): void
    {
        // Reset singleton for each test
        $reflection = new \ReflectionClass(Errbit::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $config = ['api_key' => 'test', 'host' => 'test', 'skipped_exceptions' => ['BadMethodCallException']];
        $this->errbit = new Errbit($config);
    }

    /**
     * @test
     */
    public function shouldPassExceptionsToWriter(): void
    {
        $exception = Mockery::mock(Error::class);

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write')->with($exception, Mockery::any());
        $this->errbit->setWriter($writer);

        $return = $this->errbit->notify($exception);
        $this->assertIsObject($return);
    }

    /**
     * @test
     */
    public function shouldIgnoreSkippedExceptions(): void
    {
        $this->errbit->configure(['skipped_exceptions' => [Notice::class]]);
        $previous = new \Exception('prev exception');
        $exception = new Notice('Notice test', 123, $previous);
        // don't write because this Notice should be ignored
        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class)->shouldNotReceive('write')->getMock();
        $this->errbit->setWriter($writer);
        $return = $this->errbit->notify($exception, []);
        $this->assertIsObject($return);
    }

    public function testSingletonInstance(): void
    {
        $instance1 = Errbit::instance();
        $instance2 = Errbit::instance();

        $this->assertSame($instance1, $instance2);
    }

    public function testConfigureThrowsExceptionWithoutApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("`api_key' must be configured");

        $errbit = new Errbit();
        $errbit->configure(['host' => 'test.com']);
    }

    public function testConfigureThrowsExceptionWithoutHost(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("`host' must be configured");

        $errbit = new Errbit();
        $errbit->configure(['api_key' => 'test-key']);
    }

    public function testConfigureSetsDefaultPort80ForInsecure(): void
    {
        $errbit = new Errbit();
        $errbit->configure(['api_key' => 'test', 'host' => 'test.com', 'secure' => false]);

        $reflection = new \ReflectionClass($errbit);
        $config = $reflection->getProperty('config');
        $config->setAccessible(true);
        $configValue = $config->getValue($errbit);

        $this->assertEquals(80, $configValue['port']);
    }

    public function testConfigureSetsDefaultPort443ForSecure(): void
    {
        $errbit = new Errbit();
        $errbit->configure(['api_key' => 'test', 'host' => 'test.com', 'secure' => true]);

        $reflection = new \ReflectionClass($errbit);
        $config = $reflection->getProperty('config');
        $config->setAccessible(true);
        $configValue = $config->getValue($errbit);

        $this->assertEquals(443, $configValue['port']);
    }

    public function testConfigureSetsSecureBasedOnPort(): void
    {
        $errbit = new Errbit();
        $errbit->configure(['api_key' => 'test', 'host' => 'test.com', 'port' => 443]);

        $reflection = new \ReflectionClass($errbit);
        $config = $reflection->getProperty('config');
        $config->setAccessible(true);
        $configValue = $config->getValue($errbit);

        $this->assertTrue($configValue['secure']);
    }

    public function testConfigureSetsDefaults(): void
    {
        $errbit = new Errbit();
        $errbit->configure(['api_key' => 'test', 'host' => 'test.com']);

        $reflection = new \ReflectionClass($errbit);
        $config = $reflection->getProperty('config');
        $config->setAccessible(true);
        $configValue = $config->getValue($errbit);

        $this->assertEquals('development', $configValue['environment_name']);
        $this->assertEquals(['/password/'], $configValue['params_filters']);
        $this->assertEquals(3, $configValue['connect_timeout']);
        $this->assertEquals(3, $configValue['write_timeout']);
        $this->assertEquals([], $configValue['skipped_exceptions']);
        $this->assertEquals('errbitPHP', $configValue['agent']);
        $this->assertFalse($configValue['async']);
        $this->assertEquals([], $configValue['ignore_user_agent']);
    }

    public function testOnNotifyRegistersCallback(): void
    {
        $called = false;
        $callback = function ($exception, $config) use (&$called) {
            $called = true;
        };

        $this->errbit->onNotify($callback);

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write');
        $this->errbit->setWriter($writer);

        $exception = new Error('test', 1);
        $this->errbit->notify($exception);

        $this->assertTrue($called);
    }

    public function testOnNotifyThrowsExceptionForNonCallable(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Notify callback must be callable');

        $this->errbit->onNotify('not-a-callable');
    }

    public function testOnNotifyCallbackReceivesExceptionAndConfig(): void
    {
        $receivedException = null;
        $receivedConfig = null;

        $callback = function ($exception, $config) use (&$receivedException, &$receivedConfig) {
            $receivedException = $exception;
            $receivedConfig = $config;
        };

        $this->errbit->onNotify($callback);

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write');
        $this->errbit->setWriter($writer);

        $exception = new Error('test error', 1);
        $this->errbit->notify($exception);

        $this->assertSame($exception, $receivedException);
        $this->assertIsArray($receivedConfig);
        $this->assertEquals('test', $receivedConfig['api_key']);
    }

    public function testStartReturnsInstance(): void
    {
        $result = $this->errbit->start([]);

        $this->assertSame($this->errbit, $result);
    }

    public function testNotifyReturnsInstance(): void
    {
        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write');
        $this->errbit->setWriter($writer);

        $result = $this->errbit->notify(new Error('test', 1));

        $this->assertSame($this->errbit, $result);
    }

    public function testIgnoreUserAgentSkipsNotification(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';

        $this->errbit->configure([
            'api_key' => 'test',
            'host' => 'test',
            'ignore_user_agent' => ['Googlebot']
        ]);

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldNotReceive('write');
        $this->errbit->setWriter($writer);

        $exception = new Error('test', 1);
        $this->errbit->notify($exception);

        unset($_SERVER['HTTP_USER_AGENT']);
    }

    public function testNotifyWithOptionsOverridesConfig(): void
    {
        $receivedConfig = null;

        $callback = function ($exception, $config) use (&$receivedConfig) {
            $receivedConfig = $config;
        };

        $this->errbit->onNotify($callback);

        $writer = Mockery::mock(\Errbit\Writer\WriterInterface::class);
        $writer->shouldReceive('write');
        $this->errbit->setWriter($writer);

        $exception = new Error('test', 1);
        $this->errbit->notify($exception, ['controller' => 'TestController', 'action' => 'index']);

        $this->assertEquals('TestController', $receivedConfig['controller']);
        $this->assertEquals('index', $receivedConfig['action']);
    }
}
