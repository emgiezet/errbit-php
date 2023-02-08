<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\Error;
use Errbit\Errors\ErrorInterface;
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
     * @param \Exception $exception
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function write(ErrorInterface $exception, array $config): ResponseInterface|PromiseInterface
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
