<?php

require_once dirname(__FILE__) . '/Errbit/Exception.php';
require_once dirname(__FILE__) . '/Errbit/Notice.php';
require_once dirname(__FILE__) . '/Errbit/XmlBuilder.php';

class Errbit {
	private static $_instance = null;

	public static function instance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	const VERSION       = '0.0.1';
	const API_VERSION   = '2.2';
	const PROJECT_NAME  = 'errbit-php';
	const PROJECT_URL   = 'https://github.com/flippa/errbit-php';
	const NOTICES_PATH  = '/notifier_api/v2/notices/';

	private $_config;

	public function __construct($config = array()) {
		$this->_config = $config;
	}

	public function configure($config = array()) {
		$this->_config = $config;
		$this->_checkConfig();
		return $this;
	}

	public function start() {
		$this->_checkConfig();
		//Errbit_ErrorHandlers::register($this);
		return $this;
	}

	public function notify(Exception $exception, $options = array()) {
		//var_dump($this->_buildNoticeFor($exception, $options)); return;
		$config = array_merge($this->_config, $options);

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => $this->_buildApiUrl(),
			CURLOPT_HEADER         => true,
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS     => $this->_buildNoticeFor($exception, $config),
			CURLOPT_HTTPHEADER     => array(
				'Content-Type: text/xml',
				'Accept: text/xml, application/xml'
			)
		));
		$response = curl_exec($ch);
		var_dump($response);
		return $this;
	}

	// -- Private Methods

	private function _checkConfig() {
		if (empty($this->_config['api_key'])) {
			throw new Errbit_Exception("`api_key' must be configured");
		}

		if (empty($this->_config['host'])) {
			throw new Errbit_Exception("`host' must be configured");
		}

		if (empty($this->_config['port'])) {
			$this->_config['port'] = !empty($this->_config['secure']) ? 443 : 80;
		}

		if (!isset($this->_config['secure'])) {
			$this->_config['secure'] = ($this->_config['port'] == 443);
		}

		if (empty($this->_config['hostname'])) {
			$this->_config['hostname'] = gethostname() ? gethostname() : '<unknown>';
		}

		if (empty($this->_config['project_root'])) {
			$this->_config['project_root'] = dirname(__FILE__);
		}

		if (empty($this->_config['environment_name'])) {
			$this->_config['environment_name'] = 'development';
		}
	}

	private function _buildApiUrl() {
		$this->_checkConfig();
		return implode(
			'',
			array(
				$this->_config['secure'] ? 'https://' : 'http://',
				$this->_config['host'],
				':' . $this->_config['port'],
				self::NOTICES_PATH
			)
		);
	}

	private function _buildNoticeFor(Exception $exception, $options) {
		return Errbit_Notice::forException($exception, $options)->asXml();
	}
}
