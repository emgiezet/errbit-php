<?php
namespace Errbit;

use Errbit\Exception\Exception;
use Errbit\Exception\Notice as ENotice;

use Errbit\Errors\Notice;
use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Handlers\ErrorHandlers;

/**
 * The Errbit client.
 *
 * @example Configuring the client
 *    Errbit::instance()->configure(array( ... ))->start();
 *
 * @example Notify an Exception manually
 *    Errbit::instance()->notify($exception);
 * 
 */
class Errbit
{
    private static $_instance = null;

    /**
     * Get a singleton instance of the client.
     *
     * This is the intended way to access the Errbit client.
     *
     * @return Errbit a singleton
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    const VERSION       = '0.0.1';
    const API_VERSION   = '2.2';
    const PROJECT_NAME  = 'errbit-php';
    const PROJECT_URL   = 'https://github.com/emgiezet/errbit-php';
    const NOTICES_PATH  = '/notifier_api/v2/notices/';

    private $_config;
    private $_observers = array();

    /**
     * Initialize a new client with the given config.
     *
     * This is made public for flexibility, though it is not expected you
     * should use it.
     *
     * @param array $config the configuration for the API
     */
    public function __construct($config = array())
    {
        $this->_config = $config;
    }

    /**
     * Add a handler to be invoked after a notification occurs.
     *
     * @param [Callback] $callback any callable function
     *
     * @return [Errbit] the current instance
     */
    public function onNotify($callback)
    {
        if (!is_callable($callback)) {
            throw new Exception('Notify callback must be callable');
        }

        $this->_observers[] = $callback;

        return $this;
    }

    /**
     * Set the full configuration for the client.
     *
     * The only required keys are `api_key' and `host', but other supported
     * options are:
     *
     *   - api_key
     *   - host
     *   - port
     *   - secure
     *   - project_root
     *   - environment_name
     *   - url
     *   - controller
     *   - action
     *   - session_data
     *   - parameters
     *   - cgi_data
     *   - params_filters
     *   - backtrace_filters
     *
     * @param [Array] $config the full configuration
     *
     * @return [Errbit] the current instance of the client
     */
    public function configure($config = array())
    {
        $this->_config = array_merge($this->_config, $config);
        $this->_checkConfig();

        return $this;
    }

    /**
     * Register all error handlers around this instance.
     *
     * @param [Array] $handlers an array of handler names (one or all of 'exception', 'error', 'fatal')
     *
     * @return [Errbit]
     *   the current instance
     */
    public function start($handlers = array('exception', 'error', 'fatal'))
    {
        $this->_checkConfig();
        ErrorHandlers::register($this, $handlers);

        return $this;
    }

    /**
     * Notify an individual exception manually.
     *
     * @param [Exception] $exception the Exception to notify (errors must first be converted)
     * @param [Array]     $options   an array of options, which override the client configuration
     *
     * @return [Errbit]
     *   the current instance
     */
	public function notify($exception, $options = array())
	{
		$this->_checkConfig();
		$config = array_merge($this->_config, $options);

		$socket = fsockopen(
			$this->_buildConnectionScheme($config),
			$config['port'],
			$errno, $errstr,
			$config['connect_timeout']
		);

		if ($socket) {
			stream_set_timeout($socket, $config['write_timeout']);
			$payLoad = $this->_buildPayload($exception, $config);
			if (strlen($payLoad) > 8192 && $config['async']) {
				$messageId = uniqid();
				$chunks = str_split($payLoad, 7000);
				foreach ($chunks as $idx => $chunk) {
					$packet = array(
						"messageid" => $messageId,
						"data" => $chunk
					);
					if($idx == count($chunk)) {
						$packet['last'] = true;
					}
					$fragment = json_encode($packet);
					fwrite($socket, $fragment);
				}
			} else {
				fwrite($socket, $payLoad);
			}
			fclose($socket);
		}

		foreach ($this->_observers as $observer) {
			$observer($exception, $config);
		}

		return $this;
	}

    // -- Private Methods
    /**
     * Config checker
     * 
     * @throws Exception
     * @return null
     */
    private function _checkConfig()
    {
        if (empty($this->_config['api_key'])) {
            throw new Exception("`api_key' must be configured");
        }

        if (empty($this->_config['host'])) {
            throw new Exception("`host' must be configured");
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

        if (!isset($this->_config['params_filters'])) {
            $this->_config['params_filters'] = array('/password/');
        }

        if (!isset($this->_config['connect_timeout'])) {
            $this->_config['connect_timeout'] = 3;
        }

        if (!isset($this->_config['write_timeout'])) {
            $this->_config['write_timeout'] = 3;
        }

        if (!isset($this->_config['backtrace_filters'])) {
            $this->_config['backtrace_filters'] = array(
                sprintf('/^%s/', preg_quote($this->_config['project_root'], '/')) => '[PROJECT_ROOT]'
            );
        }

        if (!isset($this->_config['agent'])) {
            $this->_config['agent'] = 'errbitPHP';
        }
    }
    /**
     * Notice Builder
     * 
     * @param mixed $exception Excpetion instance all exceptions that extends \Excpetion()
     * @param array $options   notice options
     * 
     * @return string Xml
     * 
     */
    private function _buildNoticeFor($exception, $options)
    {
        return ENotice::forException($exception, $options)->asXml();
    }
    /**
     * Build schema for Tcp
     * 
     * @param array $config config
     * 
     * @return string
     */
    private function _buildConnectionScheme($config)
    {
		$proto = "";
		if ($config['async'])
		{
			$proto = "udp";
		} else if ($config['secure']) {
			 $proto = "ssl";
		} else {
			$proto = 'tcp';
		}
        return sprintf(
            '%s://%s',
           	$proto,
            $config['host']
        );
    }
    /**
     * Build a payload to send by php fsockopen
     * 
     * @param mixed $exception Excpetion instance all exceptions that extends \Excpetion()
     * @param array $config    configuration in array
     * 
     * @return string
     */
    private function _buildPayload($exception, $config)
    {
        return $this->_addHttpHeadersIfNeeded(
            $this->_buildNoticeFor($exception, $config),
            $config
        );
    }
    /**
     * Build http headers for errbit api call
     * 
     * @param string $body   requiest body
     * @param array  $config configuration in array
     * 
     * @return string
     */
    private function _addHttpHeadersIfNeeded($body, $config)
    {
		if($config['async']) {
			return $body;
		} else {
			return sprintf(
				"%s\r\n\r\n%s",
				implode(
					"\r\n",
					array(
						sprintf('POST %s HTTP/1.1', self::NOTICES_PATH),
						sprintf('Host: %s', $config['host']),
						sprintf('User-Agent: %s', $config['agent']),
						sprintf('Content-Type: %s', 'text/xml'),
						sprintf('Accept: %s', 'text/xml, application/xml'),
						sprintf('Content-Length: %d', strlen($body)),
						sprintf('Connection: %s', 'close')
					)
				),
				$body
			);
		}
    }
}
