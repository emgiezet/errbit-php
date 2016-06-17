<?php
namespace Errbit;

use Errbit\Exception\Exception;

use Errbit\Errors\Notice;
use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Handlers\ErrorHandlers;
use Errbit\Writer\WriterInterface;

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
    private static $instance = null;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * Get a singleton instance of the client.
     *
     * This is the intended way to access the Errbit client.
     *
     * @return Errbit a singleton
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    const VERSION       = '1.0.5';
    const API_VERSION   = '2.2';
    const PROJECT_NAME  = 'errbit-php';
    const PROJECT_URL   = 'https://github.com/emgiezet/errbit-php';

    private $config;
    private $observers = array();

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
        $this->config = $config;
    }

    /**
     * @param WriterInterface $writer
     */
    public function setWriter(WriterInterface $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Add a handler to be invoked after a notification occurs.
     *
     * @param [Callback] $callback any callable function
     * @throws [Exception]
     *
     * @return [Errbit] the current instance
     */
    public function onNotify($callback)
    {
        if (!is_callable($callback)) {
            throw new Exception('Notify callback must be callable');
        }

        $this->observers[] = $callback;

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
        $this->config = array_merge($this->config, $config);
        $this->checkConfig();

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
        $this->checkConfig();
        ErrorHandlers::register($this, $handlers);

        return $this;
    }

    /**
     * Notify an individual exception manually.
     *
     * @param [Exception] $exception the Exception to notify (errors must first be converted)
     * @param [Array]     $options   an array of options, which override the client configuration
     *
     * @return [Errbit] the current instance
     */
    public function notify($exception, $options = array())
    {
        $this->checkConfig();
        $config = array_merge($this->config, $options);

        if ($this->shouldNotify($exception, $config['skipped_exceptions'])) {
            $this->getWriter()->write($exception, $config);
            $this->notifyObservers($exception, $config);
        }

        return $this;
    }

    protected function shouldNotify($exception, array $skippedExceptions)
    {
        foreach ($skippedExceptions as $skippedException) {
            if ($exception instanceof $skippedException) {
                return false;
            }
        }
        foreach ($this->config['ignore_user_agent'] as $ua) {
            if (strpos($_SERVER['HTTP_USER_AGENT']) !== false) {
                return false;
            }
        }

        return true;
    }

    protected function notifyObservers($exception, $config)
    {
        foreach ($this->observers as $observer) {
            $observer($exception, $config);
        }
    }

    protected function getWriter()
    {
        if (empty($this->writer)) {
            $defaultWriter = new $this->config['default_writer'];
            $this->writer = $defaultWriter;
        }

        return $this->writer;
    }

    // -- Private Methods
    /**
     * Config checker
     *
     * @throws Exception
     * @return null
     */
    private function checkConfig()
    {
        if (empty($this->config['api_key'])) {
            throw new Exception("`api_key' must be configured");
        }

        if (empty($this->config['host'])) {
            throw new Exception("`host' must be configured");
        }

        if (empty($this->config['port'])) {
            $this->config['port'] = !empty($this->config['secure']) ? 443 : 80;
        }

        if (!isset($this->config['secure'])) {
            $this->config['secure'] = ($this->config['port'] == 443);
        }

        if (empty($this->config['hostname'])) {
            $this->config['hostname'] = gethostname() ? gethostname() : '<unknown>';
        }

        if (empty($this->config['project_root'])) {
            $this->config['project_root'] = dirname(__FILE__);
        }

        if (empty($this->config['environment_name'])) {
            $this->config['environment_name'] = 'development';
        }

        if (!isset($this->config['params_filters'])) {
            $this->config['params_filters'] = array('/password/');
        }

        if (!isset($this->config['connect_timeout'])) {
            $this->config['connect_timeout'] = 3;
        }

        if (!isset($this->config['write_timeout'])) {
            $this->config['write_timeout'] = 3;
        }

        if (!isset($this->config['backtrace_filters'])) {
            $this->config['backtrace_filters'] = array(
                sprintf('/^%s/', preg_quote($this->config['project_root'], '/')) => '[PROJECT_ROOT]'
            );
        }

        if (!isset($this->config['skipped_exceptions'])) {
            $this->config['skipped_exceptions'] = array();
        }

        if (!isset($this->config['default_writer'])) {
            $this->config['default_writer'] = 'Errbit\Writer\SocketWriter';
        }

        if (!isset($this->config['agent'])) {
            $this->config['agent'] = 'errbitPHP';
        }
        if (!isset($this->config['async'])) {
            $this->config['async'] = false;
        }
        if (!isset($this->config['ignore_user_agent'])) {
            $this->config['ignore_user_agent'] = array();
        }
    }
}
