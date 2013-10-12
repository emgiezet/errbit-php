<?php
/**
 * Created by JetBrains PhpStorm.
 * User: deathowl
 * Date: 10/12/13
 * Time: 2:43 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Errbit\Tests\Utils;

use Errbit\Errors\Error;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;
use Errbit\Utils\Converter;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Converter
	 */
	private $object;

	public function setUp()
	{
		$this->object = Converter::createDefault();
	}

	public function testNotice()
	{
		$notice = $this->object->convert(E_NOTICE, "TestNotice", "test.php", 8, "");
		$expected = new Notice("TestNotice", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}

	public function testUserNotice()
	{

		$notice = $this->object->convert(E_USER_NOTICE, "TestNotice", "test.php", 8, "");
		$expected = new Notice("TestNotice", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}

	public function testError()
	{
		$notice = $this->object->convert(E_ERROR, "TestError", "test.php", 8, "");
		$expected = new Error("TestError", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}

	public function testUserError()
	{
		$notice = $this->object->convert(E_USER_ERROR, "TestError", "test.php", 8, "");
		$expected = new Error("TestError", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}

	public function testWarning()
	{
		$notice = $this->object->convert(E_WARNING, "TestWarning", "test.php", 8, "");
		$expected = new Warning("TestWarning", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}

	public function testUserWarning()
	{
		$notice = $this->object->convert(E_USER_WARNING, "TestWarning", "test.php", 8, "");
		$expected = new Warning("TestWarning", "test.php", 8, "");
		$this->assertEquals($notice, $expected);
	}
}