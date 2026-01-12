<?php
namespace Unit\Errbit\Tests\Errors;

use Errbit\Errors\Fatal;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FatalTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFatal()
    {
        $previous = new \Exception("prev");
        $object = new Fatal('test', 15, $previous);

        $msg = $object->getMessage();
        $this->assertEquals('test', $msg, 'Message of base error mismatch');

        $line = $object->getLine();
        $this->assertEquals(15, $line, 'Line no mismatch');

        $file = $object->getFile();
        $this->assertEquals($previous, $object->getPrevious(), 'Prev mismatch');

        $expectedTrace = [
            'line' => 1536,
            'file' => '/home/mgz/workspace/errbit-php/vendor/phpunit/phpunit/src/Framework/TestCase.php',
            'function' => 'testFatal',
            'class' => 'Unit\Errbit\Tests\Errors\FatalTest',
            'type' => '->',
        ];
        $this->assertContainsEquals($expectedTrace, $object->getTrace(), 'trace ');
    }
}
