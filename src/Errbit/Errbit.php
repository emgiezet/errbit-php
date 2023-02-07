<?php
declare(strict_types=1);
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
    private static ?\Errbit\Errbit $instance = null;

    /**
     * @var WriterInterface
     */
    protected WriterInterface $writer;

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

    public const VERSION       = '1.0.5';
    public const API_VERSION   = '2.2';
    public const PROJECT_NAME  = 'errbit-php';
    public const PROJECT_URL   = 'https://github.com/emgiezet/errbit-php';
    private array $observers = [];

    /**
     * Initialize a new client with the given config.
     *
     * This is made public for flexibility, though it is not expected you
     * should use it.
     *
     * @param array $config the configuration for the API
     */
    public function __construct(private $config = [])
    {
    }

    public function setWriter(WriterInterface $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * Add a handler to be invoked after a notification occurs.
     *
     * @param [Callback] $callback any callable function
     *
     * @throws [Exception]
     *
     * @return static the current instance
     */
    public function onNotify($callback): static
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
     * @return static the current instance of the client
     */
    public function configure($config = []): static
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
     * @return static the current instance
     */
    public function start($handlers = ['exception', 'error', 'fatal']): static
    {
        $this->checkConfig();
        ErrorHandlers::register($this, $handlers);

        return $this;
    }
    
    /**
     * Notify an individual exception manually.
     *
     * @param array $options
     *
     * @return static [Errbit] the current instance
     * @throws \Errbit\Exception\Exception
     */
    public function notify(Fatal|Errors\Warning|Notice|Error $exception, $options = []): static
    {
        $this->checkConfig();
        $config = array_merge($this->config, $options);

        if ($this->shouldNotify($exception, $config['skipped_exceptions'])) {
            $this->getWriter()->write($exception, $config);
            $this->notifyObservers($exception, $config);
        }

        return $this;
    }

    protected function shouldNotify($exception, array $skippedExceptions): bool
    {
        foreach ($skippedExceptions as $skippedException) {
            if ($exception instanceof $skippedException) {
                return false;
            }
        }
        foreach ($this->config['ignore_user_agent'] as $ua) {
            if (str_contains((string) $_SERVER['HTTP_USER_AGENT'],$ua) ) {
                return false;
            }
        }

        return true;
    }

    protected function notifyObservers($exception, array $config): void
    {
        foreach ($this->observers as $observer) {
            $observer($exception, $config);
        }
    }

    protected function getWriter(): object
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
     */
    private function checkConfig(): void
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
            $this->config['hostname'] = gethostname() ?: '<unknown>';
        }

        if (empty($this->config['project_root'])) {
            $this->config['project_root'] = __DIR__;
        }

        if (empty($this->config['environment_name'])) {
            $this->config['environment_name'] = 'development';
        }

        if (!isset($this->config['params_filters'])) {
            $this->config['params_filters'] = ['/password/'];
        }

        if (!isset($this->config['connect_timeout'])) {
            $this->config['connect_timeout'] = 3;
        }

        if (!isset($this->config['write_timeout'])) {
            $this->config['write_timeout'] = 3;
        }

        if (!isset($this->config['backtrace_filters'])) {
            $this->config['backtrace_filters'] = [sprintf('/^%s/', preg_quote((string) $this->config['project_root'], '/')) => '[PROJECT_ROOT]'];
        }

        if (!isset($this->config['skipped_exceptions'])) {
            $this->config['skipped_exceptions'] = [];
        }

        if (!isset($this->config['default_writer'])) {
            $this->config['default_writer'] = \Errbit\Writer\SocketWriter::class;
        }

        if (!isset($this->config['agent'])) {
            $this->config['agent'] = 'errbitPHP';
        }
        if (!isset($this->config['async'])) {
            $this->config['async'] = false;
        }
        if (!isset($this->config['ignore_user_agent'])) {
            $this->config['ignore_user_agent'] = [];
        }
    }
}
