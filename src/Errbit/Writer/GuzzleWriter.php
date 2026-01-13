<?php
declare(strict_types=1);
namespace Errbit\Writer;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class GuzzleWriter extends AbstractWriter implements WriterInterface
{
    /**
     * @param array<string, mixed> $config
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
        $port = isset($config['port']) ? ':' . $config['port'] : '';
        return sprintf('%s://%s%s', $proto, (string) $config['host'], $port);
    }

    public function __construct(private readonly ClientInterface $client)
    {
    }

    /**
     * @param \Throwable $exception
     * @param array<string, mixed> $config
     *
     * @return ResponseInterface|PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function write(\Throwable $exception, array $config): mixed
    {
        if ($config['async']) {
            return $this->asyncWrite($exception, $config);
        }
        return $this->synchronousWrite($exception, $config);
    }

    /**
     * @param \Throwable $exception
     * @param array<string, mixed> $config
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function synchronousWrite(\Throwable $exception, array $config): ResponseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        $body = $this->buildNoticeFor($exception, $config);

        return $this->client->request(
            'POST',
            $uri . self::NOTICES_PATH,
            [
                'body' => $body,
                'connect_timeout' => $config['connect_timeout'],
                'headers' => [
                    'Content-Type' => 'text/xml',
                    'Accept' => 'text/xml, application/xml'
                ]
            ]
        );
    }

    /**
     * @param \Throwable $exception
     * @param array<string, mixed> $config
     *
     * @return PromiseInterface
     */
    public function asyncWrite(\Throwable $exception, array $config): PromiseInterface
    {
        $uri = $this->buildConnectionScheme($config);
        return $this->client->requestAsync(
            'POST',
            $uri . self::NOTICES_PATH,
            [
                'body' => $this->buildNoticeFor($exception, $config),
                'connect_timeout' => $config['connect_timeout'],
                'headers' => [
                    'Content-Type' => 'text/xml',
                    'Accept' => 'text/xml, application/xml'
                ]
            ]
        );
    }
}
