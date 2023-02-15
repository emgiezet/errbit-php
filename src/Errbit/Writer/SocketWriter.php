<?php
/** @noinspection LoopWhichDoesNotLoopInspection */
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\ErrorInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class SocketWriter extends AbstractWriter implements WriterInterface
{
  

    /**
     * @var false|int How many characters to read after request has been made
     */
    public int|false $charactersToRead = false;
    
    /**
     * {@inheritdoc}
     *
     * @param \Errbit\Errors\ErrorInterface $exception
     * @param array $config
     *
     * @return void
     * @throws \JsonException
     * @noinspection PhpLoopNeverIteratesInspection
     */
    public function write(ErrorInterface $exception, array $config):  null|ResponseInterface|PromiseInterface
    {
        $socket = fsockopen(
            $this->buildConnectionScheme($config),
            (integer) $config['port'],
            $errno,
            $errstr,
            $config['connect_timeout']
        );

        if ($socket) {
            stream_set_timeout($socket, $config['write_timeout']);
            $payLoad = $this->buildPayload($exception, $config);
            if ($config['async'] && strlen($payLoad) > 7000 ) {
                $messageId = uniqid('', true);
                $chunks = str_split($payLoad, 7000);
                foreach ($chunks as $idx => $chunk) {
                    $packet = ['messageid' => $messageId, 'data' => $chunk];
                    if ($idx === count($chunks)-1) {
                        $packet['last'] = true;
                    }
                    $fragment = json_encode($packet, JSON_THROW_ON_ERROR);
                    fwrite($socket, $fragment);
                }
            } else {
                fwrite($socket, $payLoad);

                /**
                 * If errbit is behind a proxy, then we need read characters to make sure
                 * that request got to errbit successfully.
                 *
                 * Proxies usually do not make request to endpoints if client quits connection before
                 * proxy even gets the chance to create connection to endpoint
                 */
                if ($this->charactersToRead !== false) {
                    while (!feof($socket)) {
                        fread($socket, $this->charactersToRead);
                        break;
                    }
                }
            }

            fclose($socket);
            return null;
        }
    }
    
    /**
     * @param \Errbit\Errors\ErrorInterface $exception
     * @param array $config
     *
     * @return string
     */
    protected function buildPayload(ErrorInterface $exception, array $config): string
    {
        return $this->addHttpHeadersIfNeeded(
            $this->buildNoticeFor($exception, $config),
            $config
        );
    }
    
    /**
     * @param string $body
     * @param array $config
     *
     * @return string
     */
    protected function addHttpHeadersIfNeeded(string $body, array $config): string
    {
        if ($config['async'] ?? false) {
            return $body;
        }
    
        return sprintf(
            "%s\r\n\r\n%s",
            implode(
                "\r\n",
                [sprintf('POST %s HTTP/1.1', self::NOTICES_PATH), sprintf('Host: %s', $config['host']), sprintf('User-Agent: %s', $config['agent']), sprintf('Content-Type: %s', 'text/xml'), sprintf('Accept: %s', 'text/xml, application/xml'), sprintf('Content-Length: %d', strlen(
                    $body
                )), sprintf('Connection: %s', 'close')]
            ),
            $body
        );
    }
    
}
