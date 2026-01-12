<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Errors;

use Errbit\Errors\Error;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BaseErrorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testErrorWithAllParameters(): void
    {
        $previous = new \Exception('previous exception');
        $backtrace = [
            ['function' => 'testFunc', 'file' => '/test.php', 'line' => 10],
        ];

        $error = new Error('Test error', 42, $previous, '/path/to/file.php', $backtrace);

        $this->assertEquals('Test error', $error->getMessage());
        $this->assertEquals(42, $error->getLine());
        $this->assertEquals('/path/to/file.php', $error->getFile());
        $this->assertEquals('/path/to/file.php', $error->getErrorFile());
        $this->assertSame($previous, $error->getPrevious());
        $this->assertEquals($backtrace, $error->getBacktrace());
    }

    public function testErrorWithDefaultValues(): void
    {
        $error = new Error('Simple error');

        $this->assertEquals('Simple error', $error->getMessage());
        $this->assertEquals('', $error->getErrorFile());
        $this->assertEquals([], $error->getBacktrace());
        $this->assertNull($error->getPrevious());
    }

    public function testNoticeCreation(): void
    {
        $notice = new Notice('Notice message', 100, null, '/notice/file.php', []);

        $this->assertEquals('Notice message', $notice->getMessage());
        $this->assertEquals(100, $notice->getLine());
        $this->assertEquals('/notice/file.php', $notice->getFile());
    }

    public function testWarningCreation(): void
    {
        $warning = new Warning('Warning message', 200, null, '/warning/file.php', []);

        $this->assertEquals('Warning message', $warning->getMessage());
        $this->assertEquals(200, $warning->getLine());
        $this->assertEquals('/warning/file.php', $warning->getFile());
    }

    public function testErrorIsThrowable(): void
    {
        $error = new Error('Throwable error', 1);

        $this->assertInstanceOf(\Throwable::class, $error);
        $this->assertInstanceOf(\Exception::class, $error);
    }

    public function testErrorWithNullLine(): void
    {
        $error = new Error('Error with null line', null);

        $this->assertEquals('Error with null line', $error->getMessage());
        // Line should not be set when null
        $this->assertIsInt($error->getLine());
    }

    public function testErrorFileIsSetCorrectly(): void
    {
        $error = new Error('Error', 10, null, '/custom/path.php');

        // Both getFile() and getErrorFile() should return the custom path
        $this->assertEquals('/custom/path.php', $error->getFile());
        $this->assertEquals('/custom/path.php', $error->getErrorFile());
    }

    public function testErrorFileEmptyString(): void
    {
        $error = new Error('Error', 10, null, '');

        $this->assertEquals('', $error->getErrorFile());
        // getFile() returns the actual file where exception was created
        $this->assertStringContainsString('BaseErrorTest.php', $error->getFile());
    }
}
