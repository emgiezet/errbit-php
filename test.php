<?php

require_once 'lib/Errbit.php';

$client = Errbit::instance();
$client->configure(array(
	'api_key' => 'fedfb7520ef06c3686cc35fe338b6c58',
	'host'    => 'flippa-errbit.herokuapp.com',
	'port'    => 443,
	'secure'  => true
));

class BobException extends Exception {
}

class Bob {
	public static function doIt() {
		$bob = new self();
		$bob->a();
	}

	public function a() {
		$this->b();
	}

	public function b() {
		$this->c();
	}

	public function c() {
		throw new BobException('Example error message');
	}
}

function callBob() {
	Bob::doIt();
}

try {
	callBob();
} catch (Exception $e) {
	$client->notify($e, array('url' => 'http://bob.com/thang', 'controller'=>'BobController', 'action'=>'showBob'));
}
