<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Exception;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice as ErrorNotice;
use Errbit\Errors\Warning;
use Errbit\Exception\Notice;
use Errbit\Utils\XmlBuilder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class NoticeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function getDefaultOptions(): array
    {
        return [
            'api_key' => 'test-api-key',
            'host' => 'test.errbit.com',
            'project_root' => '/var/www/app',
            'environment_name' => 'testing',
            'params_filters' => [],
            'backtrace_filters' => [],
        ];
    }

    public function testForExceptionCreatesNotice(): void
    {
        $exception = new \Exception('Test exception');
        $options = $this->getDefaultOptions();

        $notice = Notice::forException($exception, $options);

        $this->assertInstanceOf(Notice::class, $notice);
    }

    public function testAsXmlReturnsValidXml(): void
    {
        $exception = new \Exception('Test exception');
        $options = $this->getDefaultOptions();

        $notice = new Notice($exception, $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<notice version="2.2">', $xml);
        $this->assertStringContainsString('<api-key>test-api-key</api-key>', $xml);
    }

    public function testAsXmlContainsErrorDetails(): void
    {
        $exception = new \Exception('Test error message');
        $options = $this->getDefaultOptions();

        $notice = new Notice($exception, $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<error>', $xml);
        $this->assertStringContainsString('Test error message', $xml);
        $this->assertStringContainsString('<backtrace>', $xml);
    }

    public function testAsXmlContainsServerEnvironment(): void
    {
        $options = $this->getDefaultOptions();
        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<server-environment>', $xml);
        $this->assertStringContainsString('<project-root>/var/www/app</project-root>', $xml);
        $this->assertStringContainsString('<environment-name>testing</environment-name>', $xml);
    }

    public function testClassNameReturnsNoticeForNotice(): void
    {
        $exception = new ErrorNotice('test', 1);
        $className = Notice::className($exception);

        $this->assertEquals('Notice', $className);
    }

    public function testClassNameReturnsWarningForWarning(): void
    {
        $exception = new Warning('test', 1);
        $className = Notice::className($exception);

        $this->assertEquals('Warning', $className);
    }

    public function testClassNameReturnsErrorForError(): void
    {
        $exception = new Error('test', 1);
        $className = Notice::className($exception);

        $this->assertEquals('Error', $className);
    }

    public function testClassNameReturnsFatalErrorForFatal(): void
    {
        $exception = new Fatal('test', 1);
        $className = Notice::className($exception);

        $this->assertEquals('Fatal Error', $className);
    }

    public function testClassNameReturnsClassNameForOtherExceptions(): void
    {
        $exception = new \RuntimeException('test');
        $className = Notice::className($exception);

        $this->assertEquals('RuntimeException', $className);
    }

    public function testFilterTraceReplacesPatterns(): void
    {
        $options = $this->getDefaultOptions();
        $options['backtrace_filters'] = [
            '/\/var\/www\/app/' => '[PROJECT_ROOT]',
        ];

        $notice = new Notice(new \Exception(), $options);
        $result = $notice->filterTrace('/var/www/app/src/file.php');

        $this->assertEquals('[PROJECT_ROOT]/src/file.php', $result);
    }

    public function testFilterTraceWithNoFiltersReturnsOriginal(): void
    {
        $options = $this->getDefaultOptions();
        $options['backtrace_filters'] = [];

        $notice = new Notice(new \Exception(), $options);
        $result = $notice->filterTrace('/var/www/app/src/file.php');

        $this->assertEquals('/var/www/app/src/file.php', $result);
    }

    public function testFilterTraceWithNonArrayFiltersReturnsOriginal(): void
    {
        $options = $this->getDefaultOptions();
        $options['backtrace_filters'] = 'not-an-array';

        $notice = new Notice(new \Exception(), $options);
        $result = $notice->filterTrace('/var/www/app/src/file.php');

        $this->assertEquals('/var/www/app/src/file.php', $result);
    }

    public function testFormatMethodWithClassAndType(): void
    {
        $frame = [
            'class' => 'MyClass',
            'type' => '->',
            'function' => 'myMethod',
        ];

        $result = Notice::formatMethod($frame);

        $this->assertEquals('MyClass->myMethod()', $result);
    }

    public function testFormatMethodWithStaticCall(): void
    {
        $frame = [
            'class' => 'MyClass',
            'type' => '::',
            'function' => 'staticMethod',
        ];

        $result = Notice::formatMethod($frame);

        $this->assertEquals('MyClass::staticMethod()', $result);
    }

    public function testFormatMethodWithFunctionOnly(): void
    {
        $frame = [
            'function' => 'globalFunction',
        ];

        $result = Notice::formatMethod($frame);

        $this->assertEquals('globalFunction()', $result);
    }

    public function testFormatMethodWithEmptyFrame(): void
    {
        $frame = [];

        $result = Notice::formatMethod($frame);

        $this->assertEquals('<unknown>()', $result);
    }

    public function testXmlVarsForWithSimpleArray(): void
    {
        $builder = new XmlBuilder();
        $array = ['key1' => 'value1', 'key2' => 'value2'];

        $builder->tag('root', '', [], function ($root) use ($array) {
            Notice::xmlVarsFor($root, $array);
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('key="key1"', $xml);
        $this->assertStringContainsString('value1', $xml);
        $this->assertStringContainsString('key="key2"', $xml);
        $this->assertStringContainsString('value2', $xml);
    }

    public function testXmlVarsForWithNestedArray(): void
    {
        $builder = new XmlBuilder();
        $array = [
            'outer' => [
                'inner' => 'nested value',
            ],
        ];

        $builder->tag('root', '', [], function ($root) use ($array) {
            Notice::xmlVarsFor($root, $array);
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('key="outer"', $xml);
        $this->assertStringContainsString('key="inner"', $xml);
        $this->assertStringContainsString('nested value', $xml);
    }

    public function testXmlVarsForWithObject(): void
    {
        $builder = new XmlBuilder();
        $obj = new \stdClass();
        $obj->property = 'object value';
        $array = ['myObject' => $obj];

        $builder->tag('root', '', [], function ($root) use ($array) {
            Notice::xmlVarsFor($root, $array);
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('key="myObject"', $xml);
        $this->assertStringContainsString('object value', $xml);
    }

    public function testParamsFiltering(): void
    {
        $options = $this->getDefaultOptions();
        $options['params_filters'] = ['/password/', '/secret/'];
        $options['parameters'] = [
            'username' => 'john',
            'password' => 'secret123',
            'api_secret' => 'key123',
        ];

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('john', $xml);
        $this->assertStringContainsString('[FILTERED]', $xml);
        $this->assertStringNotContainsString('secret123', $xml);
        $this->assertStringNotContainsString('key123', $xml);
    }

    public function testAsXmlWithUrl(): void
    {
        $options = $this->getDefaultOptions();
        $options['url'] = 'https://example.com/test';

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<url>https://example.com/test</url>', $xml);
    }

    public function testAsXmlWithControllerAndAction(): void
    {
        $options = $this->getDefaultOptions();
        $options['controller'] = 'UsersController';
        $options['action'] = 'show';

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<component>UsersController</component>', $xml);
        $this->assertStringContainsString('<action>show</action>', $xml);
    }

    public function testAsXmlWithUserAttributes(): void
    {
        $options = $this->getDefaultOptions();
        $options['user'] = [
            'id' => '123',
            'email' => 'user@example.com',
        ];

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<user-attributes>', $xml);
        $this->assertStringContainsString('key="id"', $xml);
        $this->assertStringContainsString('123', $xml);
    }

    public function testAsXmlWithSessionData(): void
    {
        $options = $this->getDefaultOptions();
        $options['session_data'] = ['session_id' => 'abc123'];

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<session>', $xml);
        $this->assertStringContainsString('session_id', $xml);
    }

    public function testAsXmlWithCgiData(): void
    {
        $options = $this->getDefaultOptions();
        $options['cgi_data'] = ['REQUEST_METHOD' => 'POST'];

        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<cgi-data>', $xml);
        $this->assertStringContainsString('REQUEST_METHOD', $xml);
    }

    public function testAsXmlContainsNotifierInfo(): void
    {
        $options = $this->getDefaultOptions();
        $notice = new Notice(new \Exception(), $options);
        $xml = $notice->asXml();

        $this->assertStringContainsString('<notifier>', $xml);
        $this->assertStringContainsString('<name>errbit-php</name>', $xml);
        $this->assertStringContainsString('<version>', $xml);
        $this->assertStringContainsString('<url>https://github.com/emgiezet/errbit-php</url>', $xml);
    }

    public function testBacktraceContainsLineElements(): void
    {
        $exception = new \Exception('Test');
        $options = $this->getDefaultOptions();
        $notice = new Notice($exception, $options);
        $xml = $notice->asXml();

        // Should contain backtrace with line elements
        $this->assertStringContainsString('<backtrace>', $xml);
        $this->assertStringContainsString('<line', $xml);
        $this->assertStringContainsString('number=', $xml);
        $this->assertStringContainsString('file=', $xml);
        $this->assertStringContainsString('method=', $xml);
    }
}
