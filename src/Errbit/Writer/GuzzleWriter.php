<?php

namespace Errbit\Writer;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use RectorPrefix202302\React\Promise\PromiseInterface;

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
    public function write($exception, array $config): ResponseInterface|\GuzzleHttp\Promise\PromiseInterface
    {
        if($config['async']) {
            return $this->asyncWrite($exception, $config);
        }
        return $this->synchronousWrite($exception, $config);
    
    }
    
    /**
     * @param $exception
     * @param $config
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function synchronousWrite($exception, $config): ResponseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $body = $this->buildNoticeFor($exception, $config);
        $result = $this->client->post(
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
        return $result;
    }
    
    /**
     * @param $exception
     * @param $config
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function asyncWrite($exception, $config)
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
        
        return $promise->wait();
    
    }
    
    
}
