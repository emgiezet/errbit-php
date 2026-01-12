<?php
/**
 * Created by JetBrains PhpStorm.
 * User: deathowl
 * Date: 10/12/13
 * Time: 2:43 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Unit\Errbit\Tests\Utils;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;
use Errbit\Utils\Converter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    private \Errbit\Utils\Converter $object;

    public function setUp():void
    {
        $this->object = Converter::createDefault();
    }

    public function testNotice()
    {
        $prev = new \Exception('prev');
        $notice = $this->object->convert(E_NOTICE, "TestNotice", $prev,"test.php", 8, [""]);
        $expected = new Notice("TestNotice", 8, $prev, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testUserNotice()
    {
        $prev = new \Exception('prev');
        $notice = $this->object->convert(E_USER_NOTICE, "TestNotice", $prev, "test.php", 8, [""]);
        $expected = new Notice("TestNotice", 8, $prev, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testFatalError()
    {
        $prev = new \Exception('prev');
        $fatal = $this->object->convert(E_ERROR, "TestError", $prev, "test.php", 8, [""]);
        $expected = new Fatal("TestError", 8, $prev, "test.php");
        $this->assertEquals($fatal, $expected);
    }

    public function testCatchableFatalError()
    {
        $prev = new \Exception('prev');
        $notice = $this->object->convert(E_RECOVERABLE_ERROR, "TestError",  $prev,"test.php", 8, [""]);
        $expected = new Fatal("TestError", 8, $prev, "test.php");
        $this->assertEquals($notice, $expected);
    }


    public function testUserError()
    {
        
        $notice = $this->object->convert(E_USER_ERROR, "TestError", null, "test.php", 8, [""]);
        $expected = new Error("TestError", 8, null, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testWarning()
    {
        $notice = $this->object->convert(E_WARNING, "TestWarning", null, "test.php", 8, [""]);
        $expected = new Warning("TestWarning", 8, null, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testUserWarning()
    {
        $notice = $this->object->convert(E_USER_WARNING, "TestWarning", null, "test.php", 8, [""]);
        $expected = new Warning("TestWarning", 8, null, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }
}
