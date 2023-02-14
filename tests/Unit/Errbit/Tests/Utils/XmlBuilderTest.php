<?php
namespace Unit\Errbit\Tests\Utils;

use DOMDocument;
use Errbit\Exception\Notice;
use Exception;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use StdClass;

/**
 *
 */
class XmlBuilderTest extends TestCase
{
    
    /**
     * @var array
     */
    private array $config;
    use MockeryPHPUnitIntegration;
    
    /**
     * @return void
     */
    public function setUp():void
    {
        $this->config = ['api_key'=>'9fa28ccc56ed3aae882d25a9cee5695a', 'host' => 'errbit.redexperts.net', 'port' => '80', 'secure' => '443', 'project_root' => 'test', 'environment_name' => 'test', 'url' => 'test', 'controller' => 'test', 'action' => 'test', 'session_data' => ['test'], 'parameters' => ['test', 'doink'], 'cgi_data' => ['test'], 'params_filters' => ['test'=>'/test/'], 'backtrace_filters' => 'test'];
    }
    
    /**
     * @return void
     */
    public function testBase(): void
    {

        $notice = new Notice(new Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__.'/../../../../../Resources/xsd/hoptoad_2_0.xsd');
        $this->assertTrue($valid, 'Not Valid XSD');

    }
    
    /**
     * @return void
     */
    public function testShouldNotFollowRecursion(): void
    {

        $foo = new StdClass;
        $bar = new StdClass;
        $foo->bar = $bar;
        $bar->foo = $foo;
        $vars = ['foo' => $foo, 'bar' => $bar];

        $this->config['session_data'] = [$vars];

        $notice = new Notice(new Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__.'/../../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }
    
    /**
     * @return void
     */
    public function testSimpleObjectInXml(): void
    {
        $foo = new StdClass;

        $foo->first = "First";
        $foo->second = "Second";
        $foo->third = ["1", "2"];

        $this->config['session_data'] = [$foo];

        $notice = new Notice(new Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(__DIR__.'/../../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }
}
