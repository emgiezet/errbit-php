<?php
namespace Unit\Errbit\Tests\Errors;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class WarningTest extends TestCase
{
    
    use MockeryPHPUnitIntegration;
    public function testBase()
    {
        // WIP
        $service = \Mockery::mock('service');
        $service->shouldReceive('readTemp')->times(3)->andReturn(10, 12, 14);
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
