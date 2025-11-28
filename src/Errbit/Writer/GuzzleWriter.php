<?php

declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\ErrorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

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

    /**
     * @var ClientInterface
     */
    private $_client;
    public function __construct(ClientInterface $client)
    {
        $this->_client = $client;
    }

    /**
     * @param Throwable $exception
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function write(Throwable|ErrorInterface $exception, array $config): ResponseInterface|PromiseInterface
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
    public function synchronousWrite(Throwable|ErrorInterface  $exception, array $config): ResponseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $body = $this->buildNoticeFor($exception, $config);

        return $this->_client->request(
            'POST',
            $uri . self::NOTICES_PATH,
            [
                'body' =>$body,
                'connect_timout' => $config['connect_timeout'],
                'headers'=>[
                    'Content-Type'=>'text/xml',
                    'Accept'=>'text/xml, application/xml'
                ]
            ]
        );
    }

    public function asyncWrite(Throwable|ErrorInterface $exception, array $config): PromiseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $promise = $this->_client->requestAsync(
            'POST',
            $uri . self::NOTICES_PATH,
            [
                'body' => $this->buildNoticeFor($exception, $config),
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
