<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\Error;
use Errbit\Errors\Fatal;
use Errbit\Errors\Notice;
use Errbit\Errors\Warning;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class GuzzleWriter extends AbstractWriter implements WriterInterface
{
    
    /**
     * @param $config
     *
     * @return string
     */protected function buildConnectionScheme($config): string
    {
       if ($config['secure']) {
            $proto = "https";
        } else {
            $proto = 'http';
        }
       return sprintf('%s://%s%s', $proto, $config['host'], (isset($config['port'])?':'.$config['port']:''));
    }
    
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private ClientInterface $client;
    
    /**
     * @param \GuzzleHttp\ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    
    /**
     * @param \Exception $exception
     * @param array $config
     *
     * @return \Psr\Http\Message\ResponseInterface|\GuzzleHttp\Promise\PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function write(Fatal|Warning|Notice|Error $exception, array $config): ResponseInterface|PromiseInterface
    {
        if($config['async']) {
            return $this->asyncWrite($exception, $config);
        }
        return $this->synchronousWrite($exception, $config);
    
    }
    
    /**
     * @param \Errbit\Errors\Fatal|\Errbit\Errors\Warning|\Errbit\Errors\Notice|\Errbit\Errors\Error $exception
     * @param array $config
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function synchronousWrite(Fatal|Warning|Notice|Error  $exception, array $config): ResponseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $body = $this->buildNoticeFor($exception, $config);
    
        return $this->client->post(
            uri: $uri.self::NOTICES_PATH,
            options: [
                'body' =>$body,
                'connect_timout' => $config['connect_timeout'],
                'headers'=>[
                    'Content-Type'=>'text/xml',
                    'Accept'=>'text/xml, application/xml'
                ]
            ]
        );
    }
    
    /**
     * @param \Errbit\Errors\Fatal|\Errbit\Errors\Warning|\Errbit\Errors\Notice|\Errbit\Errors\Error $exception
     * @param array $config
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function asyncWrite(Fatal|Warning|Notice|Error    $exception, array $config): PromiseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $promise = $this->client->postAsync(
            $uri.self::NOTICES_PATH,
            [
                'body' =>$this->buildNoticeFor($exception, $config),
                'connect_timout' => $config['connect_timeout'],
                'headers'=>[
                    'Content-Type'=>'text/xml',
                    'Accept'=>'text/xml, application/xml'
                ]
            ]
        );
        return $promise;
    }
}
