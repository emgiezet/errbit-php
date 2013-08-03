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

    public function testBase()
    {
        // WIP
         $config = array(
            'api_key'=>'9fa28ccc56ed3aae882d25a9cee5695a',
            'host' => 'errbit.redexperts.net',
            'hostname' => 'errbit-php-test.net',
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
        $notice = new Notice(new \Exception(), $config);

        $xml = $notice->asXml();

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate(dirname(__FILE__).'/../../../../Resources/xsd/XSD.xml');
        $this->assertTrue($valid, 'Not Valid XSD');

    }

}
