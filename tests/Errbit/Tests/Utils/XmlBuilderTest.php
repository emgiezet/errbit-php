<?php
namespace Errbit\Tests\Utils;

use \Mockery as m;
use Errbit\Exception\Notice;

class XmlBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $this->config = array(
            'api_key'=>'9fa28ccc56ed3aae882d25a9cee5695a',
            'host' => 'errbit.redexperts.net',
            'port' => '80',
            'secure' => '443',
            'project_root' => 'test',
            'environment_name' => 'test',
            'url' => 'test',
            'controller' => 'test',
            'action' => 'test',
            'session_data' => array('test',),
            'parameters' => array('test', 'doink'),
            'cgi_data' => array('test',),
            'params_filters' => array('test'=>'/test/',),
            'backtrace_filters' => 'test'
        );
    }

    public function testBase()
    {

        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(dirname(__FILE__).'/../../../../Resources/xsd/hoptoad_2_0.xsd');
        $this->assertTrue($valid, 'Not Valid XSD');

    }

    public function testShouldNotFollowRecursion()
    {

        $foo = new \StdClass;
        $bar = new \StdClass;
        $foo->bar = $bar;
        $bar->foo = $foo;
        $vars = array('foo' => $foo, 'bar' => $bar);

        $this->config['session_data'] = array($vars);

        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(dirname(__FILE__).'/../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }

    public function testSimpleObjectInXml()
    {
        $foo = new \StdClass;

        $foo->first = "First";
        $foo->second = "Second";
        $foo->third = array("1","2");

        $this->config['session_data'] = array($foo);

        $notice = new Notice(new \Exception(), $this->config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(dirname(__FILE__).'/../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');
    }
}
