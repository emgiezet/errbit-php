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
        $notice = $this->object->convert(E_NOTICE, "TestNotice", "test.php", 8, [""]);
        $expected = new Notice("TestNotice", 8, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testUserNotice()
    {

        $notice = $this->object->convert(E_USER_NOTICE, "TestNotice", "test.php", 8, [""]);
        $expected = new Notice("TestNotice", 8, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testFatalError()
    {
        $fatal = $this->object->convert(E_ERROR, "TestError", "test.php", 8, [""]);
        $expected = new Fatal("TestError", 8, "test.php");
        $this->assertEquals($fatal, $expected);
    }

    public function testCatchableFatalError()
    {
        $notice = $this->object->convert(E_RECOVERABLE_ERROR, "TestError", "test.php", 8, [""]);
        $expected = new Fatal("TestError", 8, "test.php");
        $this->assertEquals($notice, $expected);
    }


    public function testUserError()
    {
        $notice = $this->object->convert(E_USER_ERROR, "TestError", "test.php", 8, [""]);
        $expected = new Error("TestError", 8, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testWarning()
    {
        $notice = $this->object->convert(E_WARNING, "TestWarning", "test.php", 8, [""]);
        $expected = new Warning("TestWarning", 8, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }

    public function testUserWarning()
    {
        $notice = $this->object->convert(E_USER_WARNING, "TestWarning", "test.php", 8, [""]);
        $expected = new Warning("TestWarning", 8, "test.php", [""]);
        $this->assertEquals($notice, $expected);
    }
}
