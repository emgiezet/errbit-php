<?php
declare(strict_types=1);

namespace Unit\Errbit\Tests\Utils;

use Errbit\Exception\Notice;
use Errbit\Utils\XmlBuilder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class XmlBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private array $config;

    public function setUp(): void
    {
        $this->config = [
            'api_key' => '9fa28ccc56ed3aae882d25a9cee5695a',
            'host' => 'errbit.redexperts.net',
            'port' => '80',
            'secure' => '443',
            'project_root' => 'test',
            'environment_name' => 'test',
            'url' => 'test',
            'controller' => 'test',
            'action' => 'test',
            'session_data' => ['test'],
            'parameters' => ['test', 'doink'],
            'cgi_data' => ['test'],
            'params_filters' => ['test' => '/test/'],
            'backtrace_filters' => 'test'
        ];
    }

    public function testBase(): void
    {
        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__ . '/../../../../../Resources/xsd/hoptoad_2_0.xsd');
        $this->assertTrue($valid, 'Not Valid XSD');
    }

    public function testShouldNotFollowRecursion(): void
    {
        $foo = new \StdClass;
        $bar = new \StdClass;
        $foo->bar = $bar;
        $bar->foo = $foo;
        $vars = ['foo' => $foo, 'bar' => $bar];

        $this->config['session_data'] = [$vars];

        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__ . '/../../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }

    public function testSimpleObjectInXml(): void
    {
        $foo = new \StdClass;

        $foo->first = "First";
        $foo->second = "Second";
        $foo->third = ["1", "2"];

        $this->config['session_data'] = [$foo];

        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__ . '/../../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }

    // XmlBuilder specific tests

    public function testTagCreatesElement(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('test', 'value');

        $xml = $builder->asXml();

        $this->assertStringContainsString('<test>value</test>', $xml);
    }

    public function testTagWithAttributes(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('test', 'value', ['attr1' => 'val1', 'attr2' => 'val2']);

        $xml = $builder->asXml();

        $this->assertStringContainsString('attr1="val1"', $xml);
        $this->assertStringContainsString('attr2="val2"', $xml);
    }

    public function testTagWithCallback(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('parent', '', [], function ($child) {
            $child->tag('child', 'child value');
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('<parent>', $xml);
        $this->assertStringContainsString('<child>child value</child>', $xml);
    }

    public function testNestedTags(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('root', '', [], function ($root) {
            $root->tag('level1', '', [], function ($level1) {
                $level1->tag('level2', 'deep value');
            });
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('<level2>deep value</level2>', $xml);
    }

    public function testMultipleSiblingTags(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('items', '', [], function ($items) {
            $items->tag('item', 'first');
            $items->tag('item', 'second');
            $items->tag('item', 'third');
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('<item>first</item>', $xml);
        $this->assertStringContainsString('<item>second</item>', $xml);
        $this->assertStringContainsString('<item>third</item>', $xml);
    }

    public function testAttributeMethod(): void
    {
        $builder = new XmlBuilder();
        $node = $builder->tag('test', 'value');
        $node->attribute('custom', 'attr-value');

        $xml = $builder->asXml();

        $this->assertStringContainsString('custom="attr-value"', $xml);
    }

    public function testTagWithObjectValueConvertsToClassName(): void
    {
        $builder = new XmlBuilder();
        $object = new \stdClass();
        $builder->tag('test', $object);

        $xml = $builder->asXml();

        $this->assertStringContainsString('[stdClass]', $xml);
    }

    public function testTagWithIntegerValue(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('number', 42);

        $xml = $builder->asXml();

        $this->assertStringContainsString('<number>42</number>', $xml);
    }

    public function testUtf8ForXmlRemovesInvalidCharacters(): void
    {
        $invalidString = "Valid text\x00\x01\x02Invalid";

        $result = XmlBuilder::utf8ForXML($invalidString);

        $this->assertStringNotContainsString("\x00", $result);
        $this->assertStringNotContainsString("\x01", $result);
        $this->assertStringNotContainsString("\x02", $result);
        $this->assertStringContainsString('Valid text', $result);
    }

    public function testUtf8ForXmlKeepsValidCharacters(): void
    {
        $validString = "Hello World\nNew Line\tTab";

        $result = XmlBuilder::utf8ForXML($validString);

        $this->assertEquals($validString, $result);
    }

    public function testAsXmlReturnsString(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('test', 'value');

        $xml = $builder->asXml();

        $this->assertIsString($xml);
        $this->assertStringStartsWith('<?xml', $xml);
    }

    public function testTagReturnsXmlBuilder(): void
    {
        $builder = new XmlBuilder();
        $result = $builder->tag('test', 'value');

        $this->assertInstanceOf(XmlBuilder::class, $result);
    }

    public function testAttributeReturnsStaticForChaining(): void
    {
        $builder = new XmlBuilder();
        $node = $builder->tag('test', 'value');
        $result = $node->attribute('attr', 'value');

        $this->assertInstanceOf(XmlBuilder::class, $result);
    }

    public function testComplexXmlStructure(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('notice', '', ['version' => '2.2'], function ($notice) {
            $notice->tag('api-key', 'test-api-key');
            $notice->tag('notifier', '', [], function ($notifier) {
                $notifier->tag('name', 'errbit-php');
                $notifier->tag('version', '2.0.1');
            });
            $notice->tag('error', '', [], function ($error) {
                $error->tag('class', 'TestError');
                $error->tag('message', 'Test error message');
            });
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('version="2.2"', $xml);
        $this->assertStringContainsString('<api-key>test-api-key</api-key>', $xml);
        $this->assertStringContainsString('<name>errbit-php</name>', $xml);
        $this->assertStringContainsString('<class>TestError</class>', $xml);
    }

    public function testTagWithMultipleChildren(): void
    {
        $builder = new XmlBuilder();
        $builder->tag('parent', '', [], function ($parent) {
            $parent->tag('child', 'first');
            $parent->tag('child', 'second');
        });

        $xml = $builder->asXml();

        $this->assertStringContainsString('<child>first</child>', $xml);
        $this->assertStringContainsString('<child>second</child>', $xml);
    }
}
