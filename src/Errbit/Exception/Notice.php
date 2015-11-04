<?php
/**
 * Errbit PHP Notifier.
 *
 * Copyright © Flippa.com Pty. Ltd.
 * See the LICENSE file for details.
 *
 * @category   Errors
 * @package    ErrbitPHP
 * @subpackage Errbit
 * @author     Flippa <flippa@Flippa.com>
 * @author     Max Malecki <emgiezet@github.com>
 * @license    https://github.com/emgiezet/errbit-php/blob/master/LICENSE MIT
 * @link       https://github.com/emgiezet/errbit-php Repo
 */
namespace Errbit\Exception;

use Errbit\Utils\XmlBuilder;
use Errbit\Errbit;

/**
 * Builds a complete payload for the notice sent to Errbit.
 *
 * @category   Errors
 * @package    ErrbitPHP
 * @subpackage Errbit
 * @author     Max Malecki <emgiezet@github.com>
 * @license    https://github.com/emgiezet/errbit-php/blob/master/LICENSE MIT
 * @link       https://github.com/emgiezet/errbit-php/ repo
 */
class Notice
{
    private $exception;
    private $options;

    private static $hashArray = array();

    /**
     * Create a new notice for the given Exception with the given $options.
     *
     * @param mixed $exception - the exception that occurred
     * @param array $options   - full configuration + options
     */
    public function __construct($exception, $options = array())
    {
        $this->exception = $exception;
        $this->options   = array_merge(
            array(
                'url'          => $this->buildRequestUrl(),
                'parameters'   => !empty($_REQUEST) ? $_REQUEST : array(),
                'session_data' => !empty($_SESSION) ? $_SESSION : array(),
                'cgi_data'     => !empty($_SERVER)  ? $_SERVER  : array()
            ),
            $options
        );

        $this->filterData();
    }

    /**
     * Convenience method to instantiate a new notice.
     *
     * @param mixed $exception - Exception
     * @param mixed $options   - array of options
     *
     * @return Notice
     */
    public static function forException($exception, $options = array())
    {
        return new self($exception, $options);
    }

    /**
     * Extract a human-readable method/function name from the given stack frame.
     *
     * @param array $frame - a single entry for the backtrace
     *
     * @return string -  the name of the method/function
     */
    public static function formatMethod($frame)
    {
        if (!empty($frame['class']) && !empty($frame['type']) && !empty($frame['function'])) {
            return sprintf('%s%s%s()', $frame['class'], $frame['type'], $frame['function']);
        } else {
            return sprintf('%s()', !empty($frame['function']) ? $frame['function'] : '<unknown>');
        }
    }

    /**
     * Get a human readable class name for the Exception.
     *
     * Native PHP errors are named accordingly.
     *
     * @param Exception $exception - the exception object
     *
     * @return string - the name to display
     */
    public static function className($exception)
    {
        $shortClassname = self::parseClassname(get_class($exception));
        switch ($shortClassname['classname']) {
            case 'Notice':
                return 'Notice';
            case 'Warning':
                return 'Warning';
            case 'Error':
                return 'Error';
            case 'Fatal':
                return 'Fatal Error';
            default:
                return $shortClassname['classname'];
        }
    }

    /**
     * Recursively build an list of the all the vars in the given array.
     *
     * @param Errbit\XmlBuilder $builder the builder instance to set the data into
     * @param array             $array   the stack frame entry
     *
     * @return null
     */
    public static function xmlVarsFor(XmlBuilder $builder, $array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_object($value)) {

                    $hash = spl_object_hash($value);

                    $value = (array) $value;
                } else {
                    $hash = null;
                }

                if (is_array($value)) {
                    if (null == $hash || !in_array($hash, self::$hashArray)) {
                        self::$hashArray[]= $hash;
                        $builder->tag(
                            'var',
                            '',
                            array('key' => $key),
                            function ($var) use ($value) {
                                Notice::xmlVarsFor($var, $value);
                            },
                            true
                        );
                    } else {
                        $builder->tag(
                            'var',
                            '*** RECURSION ***',
                            array(
                                'key' => $key
                            )
                        );
                    }

                } else {
                    $builder->tag('var', $value, array('key' => $key));
                }
            }
        }
    }

    /**
     * Perform search/replace filters on a backtrace entry.
     *
     * @param string $str the entry from the backtrace
     *
     * @return string the filtered entry
     */
    public function filterTrace($str)
    {

        if (empty($this->options['backtrace_filters']) || !is_array($this->options['backtrace_filters'])) {
            return $str;
        }

        foreach ($this->options['backtrace_filters'] as $pattern => $replacement) {
            $str = preg_replace($pattern, $replacement, $str);
        }

        return $str;
    }

    /**
     * Build the full XML document for the notice.
     *
     * @return string the XML
     */
    public function asXml()
    {
        $exception = $this->exception;
        $options   = $this->options;
        $builder   = new XmlBuilder();
        $self      = $this;

        return $builder->tag(
            'notice',
            '',
            array('version' => Errbit::API_VERSION),
            function (XmlBuilder $notice) use ($exception, $options, $self) {
                $notice->tag('api-key', $options['api_key']);
                $notice->tag(
                    'notifier',
                    '',
                    array(),
                    function (XmlBuilder $notifier) {
                        $notifier->tag('name', Errbit::PROJECT_NAME);
                        $notifier->tag('version', Errbit::VERSION);
                        $notifier->tag('url', Errbit::PROJECT_URL);
                    }
                );

                $notice->tag(
                    'error',
                    '',
                    array(),
                    function (XmlBuilder $error) use ($exception, $self) {
                        $class = Notice::className($exception);
                        $error->tag('class', $self->filterTrace($class));
                        $error->tag(
                            'message',
                            $self->filterTrace(sprintf('%s: %s', $class, $exception->getMessage()))
                        );
                        $error->tag(
                            'backtrace',
                            '',
                            array(),
                            function (XmlBuilder $backtrace) use ($exception, $self) {
                                $trace = $exception->getTrace();

                                $file1 = $exception->getFile();
                                $backtrace->tag(
                                    'line',
                                    '',
                                    array(
                                        'number' => $exception->getLine(),
                                        'file' => !empty($file1) ? $self->filterTrace($file1) : '<unknown>',
                                        'method' =>  "<unknown>"
                                    )
                                );

                                // if there is no trace we should add an empty element
                                if (empty($trace)) {
                                    $backtrace->tag(
                                        'line',
                                        '',
                                        array(
                                            'number' => '',
                                            'file' => '',
                                            'method' => ''
                                        )
                                    );
                                } else {
                                    foreach ($trace as $frame) {
                                        $backtrace->tag(
                                            'line',
                                            '',
                                            array(
                                                'number' => isset($frame['line']) ? $frame['line'] : 0,
                                                'file'   => isset($frame['file']) ?
                                                    $self->filterTrace($frame['file']) : '<unknown>',
                                                'method' => $self->filterTrace($self->formatMethod($frame))
                                            )
                                        );
                                    }
                                }
                            }
                        );
                    }
                );

                if (!empty($options['url'])
                    || !empty($options['controller'])
                    || !empty($options['action'])
                    || !empty($options['parameters'])
                    || !empty($options['session_data'])
                    || !empty($options['cgi_data'])
                ) {
                    $notice->tag(
                        'request',
                        '',
                        array(),
                        function (XmlBuilder $request) use ($options) {
                            $request->tag('url', !empty($options['url']) ? $options['url'] : '');
                            $request->tag('component', !empty($options['controller']) ? $options['controller'] : '');
                            $request->tag('action', !empty($options['action']) ? $options['action'] : '');
                            if (!empty($options['parameters'])) {
                                $request->tag(
                                    'params',
                                    '',
                                    array(),
                                    function (XmlBuilder $params) use ($options) {
                                        Notice::xmlVarsFor($params, $options['parameters']);
                                    }
                                );
                            }

                            if (!empty($options['session_data'])) {
                                $request->tag(
                                    'session',
                                    '',
                                    array(),
                                    function (XmlBuilder $session) use ($options) {
                                        Notice::xmlVarsFor($session, $options['session_data']);
                                    }
                                );
                            }

                            if (!empty($options['cgi_data'])) {
                                $request->tag(
                                    'cgi-data',
                                    '',
                                    array(),
                                    function (XmlBuilder $cgiData) use ($options) {
                                        Notice::xmlVarsFor($cgiData, $options['cgi_data']);
                                    }
                                );
                            }
                        }
                    );
                }
                
                if (!empty($options['user'])) {
                    $notice->tag(
                        'user-attributes',
                        '',
                        array(),
                        function (XmlBuilder $user) use ($options) {
                            Notice::xmlVarsFor($user, $options['user']);
                        }
                    );
                }

                $notice->tag(
                    'server-environment',
                    '',
                    array(),
                    function (XmlBuilder $env) use ($options) {
                        $env->tag('project-root', $options['project_root']);
                        $env->tag('environment-name', $options['environment_name']);
//                        $env->tag('hostname', $options['hostname']);
                    }
                );
            }
        )->asXml();
    }

    // -- Private Methods
    /**
     * Filtering data
     *
     * @return null
     */
    private function filterData()
    {
        if (empty($this->options['params_filters'])) {
            return;
        }

        foreach (array('parameters', 'session_data', 'cgi_data') as $name) {
            $this->filterParams($name);
        }
    }
    /**
     * Filtering params
     *
     * @param strin $name param name
     *
     * @return null
     */
    private function filterParams($name)
    {
        if (empty($this->options[$name])) {
            return;
        }

        if (is_array($this->options['params_filters'])) {
            foreach ($this->options['params_filters'] as $pattern) {
                foreach ($this->options[$name] as $key => $value) {

                    if (preg_match($pattern, $key)) {
                        $this->options[$name][$key] = '[FILTERED]';
                    }
                }
            }
        }
    }

    /**
     * Building request url
     *
     * @return string url
     *
     */
    private function buildRequestUrl()
    {
        if (!empty($_SERVER['REQUEST_URI'])) {
            return sprintf(
                '%s://%s%s%s',
                $this->guessProtocol(),
                $this->guessHost(),
                $this->guessPort(),
                $_SERVER['REQUEST_URI']
            );
        }
    }
    /**
     *  Protocol guesser
     *
     * @return string http or https protocol
     */
    private function guessProtocol()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return 'https';
        } else {
            return 'http';
        }
    }
    /**
     * Host guesser
     *
     * @return string servername
     */
    private function guessHost()
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        } else {
            return '127.0.0.1';
        }
    }
    /**
     * Port guesser
     *
     * @return string port
     *
     */
    private function guessPort()
    {
        if (!empty($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], array(80, 443))) {
            return sprintf(':%d', $_SERVER['SERVER_PORT']);
        }
    }
    /**
     * Parses class name to namespace and class name.
     * @param string $name Name of class
     * @return array
     *
     */
    private static function parseClassname ($name)
    {
        return array(
            'namespace' => array_slice(explode('\\', $name), 0, -1),
            'classname' => join('', array_slice(explode('\\', $name), -1)),
        );
    }
}
