<?php
declare(strict_types=1);

namespace Errbit\Exception;

use Errbit\Errbit;
use Errbit\Utils\XmlBuilder;

class Notice
{
    
    /**
     * @var array
     */
    private static array $hashArray = [];
    
    /**
     * @var array
     */
    private array $options;
    
    /**
     * Create a new notice for the given Exception with the given $options.
     *
     * @param mixed $exception - the exception that occurred
     * @param array $options - full configuration + options
     */
    public function __construct(
        private  mixed $exception,
        array $options = []
    ) {
        $this->options = array_merge(
            [
                'url' => $this->buildRequestUrl(),
                'parameters' => !empty($_REQUEST) ? $_REQUEST : [],
                'session_data' => !empty($_SESSION) ? $_SESSION : [],
                'cgi_data' => !empty($_SERVER) ? $_SERVER : [],
            ],
            $options
        );
        
        $this->filterData();
    }
    
    /**
     * Building request url
     *
     * @return string url
     *
     */
    private function buildRequestUrl(): ?string
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
        
        return null;
    }
    
    /**
     * Protocol guesser
     *
     * @return string http or https protocol
     */
    private function guessProtocol(): string
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
    private function guessHost(): string
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
     */
    private function guessPort(): string
    {
        if (!empty($_SERVER['SERVER_PORT']) && !in_array(
                $_SERVER['SERVER_PORT'],
                [80, 443]
            )) {
            return sprintf(':%d', $_SERVER['SERVER_PORT']);
        }
        
        return '80';
    }
    
    /**
     * Filtering data
     *
     * @return void
     */
    private function filterData(): void
    {
        if (empty($this->options['params_filters'])) {
            return;
        }
        
        foreach (['parameters', 'session_data', 'cgi_data'] as $name) {
            $this->filterParams($name);
        }
    }
    
    /**
     * Filtering params
     *
     * @param string $name param name
     *
     * @return void
     */
    private function filterParams(string $name): void
    {
        if (empty($this->options[$name])) {
            return;
        }
        
        if (is_array($this->options['params_filters'])) {
            foreach ($this->options['params_filters'] as $pattern) {
                foreach ($this->options[$name] as $key => $value) {
                    
                    if (preg_match($pattern, (string)$key)) {
                        $this->options[$name][$key] = '[FILTERED]';
                    }
                }
            }
        }
    }
    
    // -- Private Methods
    
    /**
     * Convenience method to instantiate a new notice.
     *
     * @param mixed $exception - Exception
     * @param array $options - array of options
     *
     * @return Notice
     */
    public static function forException(
        mixed $exception,
        array $options = []
    ): Notice {
        return new self($exception, $options);
    }
    
    /**
     * Build the full XML document for the notice.
     *
     * @return string the XML
     */
    public function asXml(): string
    {
        $exception = $this->exception;
        $options = $this->options;
        $builder = new XmlBuilder();
        $self = $this;
        
        return $builder->tag(
            'notice',
            '',
            ['version' => Errbit::API_VERSION],
            function (XmlBuilder $notice) use ($exception, $options, $self) {
                $notice->tag('api-key', $options['api_key']);
                $notice->tag(
                    'notifier',
                    '',
                    [],
                    function (XmlBuilder $notifier) {
                        $notifier->tag('name', Errbit::PROJECT_NAME);
                        $notifier->tag('version', Errbit::VERSION);
                        $notifier->tag('url', Errbit::PROJECT_URL);
                    }
                );
                
                $notice->tag(
                    'error',
                    '',
                    [],
                    function (XmlBuilder $error) use ($exception, $self) {
                        $class = Notice::className($exception);
                        $error->tag('class', $self->filterTrace($class));
                        $error->tag(
                            'message',
                            $self->filterTrace(
                                sprintf(
                                    '%s: %s',
                                    $class,
                                    $exception->getMessage()
                                )
                            )
                        );
                        $error->tag(
                            'backtrace',
                            '',
                            [],
                            function (XmlBuilder $backtrace) use (
                                $exception,
                                $self
                            ) {
                                $trace = $exception->getTrace();
                                
                                $file1 = $exception->getFile();
                                $backtrace->tag(
                                    'line',
                                    '',
                                    [
                                        'number' => $exception->getLine(),
                                        'file' => !empty($file1) ? $self->filterTrace(
                                            $file1
                                        ) : '<unknown>',
                                        'method' => "<unknown>",
                                    ]
                                );
                                
                                // if there is no trace we should add an empty element
                                if (empty($trace)) {
                                    $backtrace->tag(
                                        'line',
                                        '',
                                        [
                                            'number' => '',
                                            'file' => '',
                                            'method' => '',
                                        ]
                                    );
                                } else {
                                    foreach ($trace as $frame) {
                                        $backtrace->tag(
                                            'line',
                                            '',
                                            [
                                                'number' => $frame['line'] ?? 0,
                                                'file' => isset($frame['file']) ?
                                                    $self->filterTrace(
                                                        $frame['file']
                                                    ) : '<unknown>',
                                                'method' => $self->filterTrace(
                                                    $self->formatMethod($frame)
                                                ),
                                            ]
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
                        [],
                        function (XmlBuilder $request) use ($options) {
                            $request->tag(
                                'url',
                                !empty($options['url']) ? $options['url'] : ''
                            );
                            $request->tag(
                                'component',
                                !empty($options['controller']) ? $options['controller'] : ''
                            );
                            $request->tag(
                                'action',
                                !empty($options['action']) ? $options['action'] : ''
                            );
                            if (!empty($options['parameters'])) {
                                $request->tag(
                                    'params',
                                    '',
                                    [],
                                    function (XmlBuilder $params) use ($options
                                    ) {
                                        Notice::xmlVarsFor(
                                            $params,
                                            $options['parameters']
                                        );
                                    }
                                );
                            }
                            
                            if (!empty($options['session_data'])) {
                                $request->tag(
                                    'session',
                                    '',
                                    [],
                                    function (XmlBuilder $session) use ($options
                                    ) {
                                        Notice::xmlVarsFor(
                                            $session,
                                            $options['session_data']
                                        );
                                    }
                                );
                            }
                            
                            if (!empty($options['cgi_data'])) {
                                $request->tag(
                                    'cgi-data',
                                    '',
                                    [],
                                    function (XmlBuilder $cgiData) use ($options
                                    ) {
                                        Notice::xmlVarsFor(
                                            $cgiData,
                                            $options['cgi_data']
                                        );
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
                        [],
                        function (XmlBuilder $user) use ($options) {
                            Notice::xmlVarsFor($user, $options['user']);
                        }
                    );
                }
                
                $notice->tag(
                    'server-environment',
                    '',
                    [],
                    function (XmlBuilder $env) use ($options) {
                        $env->tag('project-root', $options['project_root']);
                        $env->tag(
                            'environment-name',
                            $options['environment_name']
                        );
                    }
                );
            }
        )->asXml();
    }
    
    /**
     * Get a human readable class name for the Exception.
     *
     * Native PHP errors are named accordingly.
     *
     * @param object $exception - the exception object
     *
     * @return string - the name to display
     */
    public static function className(object $exception): string
    {
        $shortClassname = self::parseClassname($exception::class);
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
     * Parses class name to namespace and class name.
     *
     * @param string $name Name of class
     *
     * @return (string|string[])[]
     *
     * @psalm-return array{namespace: list<string>, classname: string}
     */
    private static function parseClassname(string $name): array
    {
        return [
            'namespace' => array_slice(explode('\\', $name), 0, -1),
            'classname' => implode('', array_slice(explode('\\', $name), -1)),
        ];
    }
    
    /**
     * Perform search/replace filters on a backtrace entry.
     *
     * @param string $str the entry from the backtrace
     *
     * @return string the filtered entry
     */
    public function filterTrace(string $str): string
    {
        
        if (empty($this->options['backtrace_filters']) || !is_array(
                $this->options['backtrace_filters']
            )) {
            return $str;
        }
        
        foreach ($this->options['backtrace_filters'] as $pattern => $replacement) {
            $str = preg_replace($pattern, (string)$replacement, $str);
        }
        
        return $str;
    }
    
    /**
     * Extract a human-readable method/function name from the given stack frame.
     *
     * @param array $frame - a single entry for the backtrace
     *
     * @return string -  the name of the method/function
     */
    public static function formatMethod(array $frame): string
    {
        if (!empty($frame['class']) && !empty($frame['type']) && !empty($frame['function'])) {
            return sprintf(
                '%s%s%s()',
                $frame['class'],
                $frame['type'],
                $frame['function']
            );
        } else {
            return sprintf(
                '%s()',
                !empty($frame['function']) ? $frame['function'] : '<unknown>'
            );
        }
    }
    
    /**
     * Recursively build an list of the all the vars in the given array.
     *
     * @param \Errbit\Utils\XmlBuilder $builder the builder instance to set the
     *     data into
     * @param array $array the stack frame entry
     *
     * @return void
     */
    public static function xmlVarsFor(XmlBuilder $builder, array $array): void
    {
        
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                
                $hash = spl_object_hash($value);
                
                $value = (array)$value;
            } else {
                $hash = null;
            }
            
            if (is_array($value)) {
                if (null === $hash || !in_array($hash, self::$hashArray)) {
                    self::$hashArray[] = $hash;
                    $builder->tag(
                        'var',
                        '',
                        ['key' => $key],
                        function ($var) use ($value) {
                            Notice::xmlVarsFor($var, $value);
                        },
                        true
                    );
                } else {
                    $builder->tag(
                        'var',
                        '*** RECURSION ***',
                        ['key' => $key]
                    );
                }
                
            } else {
                $builder->tag('var', $value, ['key' => $key]);
            }
        }
    }
}
