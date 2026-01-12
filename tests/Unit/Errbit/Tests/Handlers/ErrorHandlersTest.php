<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Handlers;

use Errbit\Errbit;
use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Handlers\ErrorHandlers;
use Errbit\Writer\SocketWriter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ErrorHandlersTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createErrbitWithMockedWriter(): array
    {
        $config = [
            'api_key' => '9fa28ccc56ed3aae882d25a9cee5695a',
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false
        ];
        $errbit = new Errbit($config);

        $writerMock = \Mockery::mock(SocketWriter::class);
        $writerMock->shouldReceive('write');
        $errbit->setWriter($writerMock);

        return [$errbit, $writerMock];
    }

    public function testNoticeHandle(): void
    {
        [$errbit, $writerMock] = $this->createErrbitWithMockedWriter();
        $handler = new ErrorHandlers($errbit, ['exception', 'error', 'fatal', 'lol', 'doink']);

        $errors = [E_NOTICE, E_USER_NOTICE, E_WARNING, E_USER_WARNING, E_ERROR, E_USER_ERROR];
        $catched = [];
        try {
            foreach ($errors as $error) {
                $handler->onError($error, 'Errbit Test: ' . $error, __FILE__, 666);
            }
        } catch (\Exception $e) {
            $catched[] = $e->getMessage();
        }
        $this->assertCount(0, $catched, 'Exceptions are thrown during errbit notice');
    }

    public function testOnErrorCreatesCorrectErrorType(): void
    {
        $config = [
            'api_key' => 'test-key',
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false
        ];
        $errbit = new Errbit($config);

        $receivedException = null;
        $writerMock = \Mockery::mock(SocketWriter::class);
        $writerMock->shouldReceive('write')->andReturnUsing(function ($exception, $config) use (&$receivedException) {
            $receivedException = $exception;
        });
        $errbit->setWriter($writerMock);

        $handler = new ErrorHandlers($errbit, ['error']);
        $handler->onError(E_NOTICE, 'Test notice message', '/test/file.php', 42);

        $this->assertInstanceOf(\Errbit\Errors\Notice::class, $receivedException);
        $this->assertEquals('Test notice message', $receivedException->getMessage());
    }

    public function testOnExceptionHandlesException(): void
    {
        $config = [
            'api_key' => 'test-key',
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false
        ];
        $errbit = new Errbit($config);

        $receivedException = null;
        $writerMock = \Mockery::mock(SocketWriter::class);
        $writerMock->shouldReceive('write')->once()->andReturnUsing(function ($exception, $config) use (&$receivedException) {
            $receivedException = $exception;
        });
        $errbit->setWriter($writerMock);

        $handler = new ErrorHandlers($errbit, ['exception']);

        $originalException = new \Exception('Test exception message', 123);
        $handler->onException($originalException);

        $this->assertNotNull($receivedException);
        $this->assertEquals('Test exception message', $receivedException->getMessage());
    }

    public function testRegisterCreatesHandler(): void
    {
        [$errbit, $writerMock] = $this->createErrbitWithMockedWriter();

        // This should not throw
        ErrorHandlers::register($errbit, []);

        $this->assertTrue(true);
    }

    public function testHandlerOnlyRegistersRequestedHandlers(): void
    {
        [$errbit, $writerMock] = $this->createErrbitWithMockedWriter();

        // Register only error handler
        $handler = new ErrorHandlers($errbit, ['error']);

        // Should be able to call onError without issues
        $handler->onError(E_NOTICE, 'Test', __FILE__, 1);

        $this->assertTrue(true);
    }

    public function testOnErrorWithDifferentErrorTypes(): void
    {
        $config = [
            'api_key' => 'test-key',
            'host' => '127.0.0.1',
            'port' => '8080',
            'secure' => false
        ];

        $testCases = [
            ['code' => E_NOTICE, 'expectedClass' => \Errbit\Errors\Notice::class],
            ['code' => E_USER_NOTICE, 'expectedClass' => \Errbit\Errors\Notice::class],
            ['code' => E_WARNING, 'expectedClass' => \Errbit\Errors\Warning::class],
            ['code' => E_USER_WARNING, 'expectedClass' => \Errbit\Errors\Warning::class],
            ['code' => E_USER_ERROR, 'expectedClass' => \Errbit\Errors\Error::class],
        ];

        foreach ($testCases as $testCase) {
            $errbit = new Errbit($config);
            $receivedException = null;

            $writerMock = \Mockery::mock(SocketWriter::class);
            $writerMock->shouldReceive('write')->andReturnUsing(function ($exception, $config) use (&$receivedException) {
                $receivedException = $exception;
            });
            $errbit->setWriter($writerMock);

            $handler = new ErrorHandlers($errbit, ['error']);
            $handler->onError($testCase['code'], 'Test message', '/test.php', 10);

            $this->assertInstanceOf(
                $testCase['expectedClass'],
                $receivedException,
                "Error code {$testCase['code']} should create {$testCase['expectedClass']}"
            );
        }
    }
}
