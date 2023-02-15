<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\ErrorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class GuzzleWriter extends AbstractWriter implements WriterInterface
{
    
    /**
     * @param array $config
     *
     * @return string
     */
    protected function buildConnectionScheme(array $config): string
    {
       if ($config['secure']) {
            $proto = "https";
        } else {
            $proto = 'http';
        }
       return sprintf('%s://%s%s', $proto, $config['host'], (isset($config['port'])?':'.$config['port']:''));
    }
    
    public function __construct(private ClientInterface $client)
    {
    }
    
    /**
     * @param \Errbit\Errors\ErrorInterface $exception
     * @param array $config
     *
     * @return \Psr\Http\Message\ResponseInterface|\GuzzleHttp\Promise\PromiseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function write(ErrorInterface $exception, array $config): null|ResponseInterface|PromiseInterface
    {
        if($config['async']) {
            return $this->asyncWrite($exception, $config);
        }
        return $this->synchronousWrite($exception, $config);
    
    }
    
    /**
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function synchronousWrite(ErrorInterface  $exception, array $config): ResponseInterface
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
    
    public function asyncWrite(ErrorInterface $exception, array $config): PromiseInterface
    {
        $uri = $this->buildConnectionScheme($config);
    
        return $this->client->postAsync(
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
    }
}
