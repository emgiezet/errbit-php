<?php
namespace Unit\Errbit\Tests\Exception;

use Errbit\Exception\Notice;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class NoticeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBase(): void
    {
        $options = [
            'api_key'=>'test',
            'project_root'=>'test',
            'environment_name'=>'test'
        ];
        $exception = \Mockery::mock('\Exception');
        $instance = new Notice($exception, $options);
        
        $result = $instance->asXml();
        $this->assertStringContainsString('Exception', $result);
    }
}
