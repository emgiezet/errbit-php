<?php

namespace Errbit\Writer;

use Errbit\Exception\Notice;

class SocketWriter implements WriterInterface
{
    /**
     * Hoptoad Notifier Route
     */
    const NOTICES_PATH  = '/notifier_api/v2/notices/';

    /**
     * {@inheritdoc}
     */
    public function write($exception, array $config)
    {
        $socket = fsockopen(
            $this->buildConnectionScheme($config),
            $config['port'],
            $errno,
            $errstr,
            $config['connect_timeout']
        );

        if ($socket) {
            stream_set_timeout($socket, $config['write_timeout']);
            $payLoad = $this->buildPayload($exception, $config);
            if (strlen($payLoad) > 7000 && $config['async']) {
                $messageId = uniqid();
                $chunks = str_split($payLoad, 7000);
                foreach ($chunks as $idx => $chunk) {
                    $packet = array(
                        "messageid" => $messageId,
                        "data" => $chunk
                    );
                    if ($idx == count($chunks)-1) {
                        $packet['last'] = true;
                    }
                    $fragment = json_encode($packet);
                    fwrite($socket, $fragment);
                }
            } else {
                fwrite($socket, $payLoad);
            }
            fclose($socket);
        }
    }

    protected function buildPayload($exception, $config)
    {
        return $this->addHttpHeadersIfNeeded(
            $this->buildNoticeFor($exception, $config),
            $config
        );
    }



    protected function buildConnectionScheme($config)
    {
        $proto = "";
        if ($config['async']) {
            $proto = "udp";
        } elseif ($config['secure']) {
             $proto = "ssl";
        } else {
            $proto = 'tcp';
        }

        return sprintf('%s://%s', $proto, $config['host']);
    }

    protected function addHttpHeadersIfNeeded($body, $config)
    {
        if ($config['async']) {
            return $body;
        } else {
            return sprintf(
                "%s\r\n\r\n%s",
                implode(
                    "\r\n",
                    array(
                        sprintf('POST %s HTTP/1.1', self::NOTICES_PATH),
                        sprintf('Host: %s', $config['host']),
                        sprintf('User-Agent: %s', $config['agent']),
                        sprintf('Content-Type: %s', 'text/xml'),
                        sprintf('Accept: %s', 'text/xml, application/xml'),
                        sprintf('Content-Length: %d', strlen($body)),
                        sprintf('Connection: %s', 'close')
                    )
                ),
                $body
            );
        }
    }

    protected function buildNoticeFor($exception, $options)
    {
        return Notice::forException($exception, $options)->asXml();
    }
}
