<?php
namespace Unit\Errbit\Tests\Errors;

use Errbit\Errors\Fatal;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FatalTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFatal(): void
    {
        $previous = new \Exception("prev");
        $object = new Fatal('test', 15, $previous);

        $this->assertEquals('test', $object->getMessage(), 'Message of base error mismatch');
        $this->assertEquals(15, $object->getLine(), 'Line no mismatch');
        $this->assertEquals($previous, $object->getPrevious(), 'Prev mismatch');

        // Check that trace contains this test method (without checking specific line numbers)
        $trace = $object->getTrace();
        $found = false;
        foreach ($trace as $frame) {
            if (isset($frame['function']) && $frame['function'] === 'testFatal'
                && isset($frame['class']) && $frame['class'] === self::class) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Trace should contain the test method');
    }

    public function testFatalWithFile(): void
    {
        $object = new Fatal('error message', 42, null, '/path/to/file.php');

        $this->assertEquals('error message', $object->getMessage());
        $this->assertEquals(42, $object->getLine());
        $this->assertEquals('/path/to/file.php', $object->getFile());
        $this->assertNull($object->getPrevious());
    }
}
