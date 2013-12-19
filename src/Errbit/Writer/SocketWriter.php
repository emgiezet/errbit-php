<?php

namespace Errbit\Writer;

use Errbit\Exception\Notice;
use Guzzle\Http\Client;

class SocketWriter implements WriterInterface
{
    const NOTICES_PATH  = '/notifier_api/v2/notices/';

	/**
	 * {@inheritdoc}
	 */
	public function write($exception, array $config)
	{
        $headers = array(
            'Host'=> $config['host'],
            'Accept'=>'text/xml, application/xml',
            'Content-Type'=> 'text/xml'

        );
        $client = new Client($this->buildConnectionScheme($config));
        $client->setUserAgent($config['agent']);
        $client->post(self::NOTICES_PATH, $headers, $this->buildPayload($exception, $config), array( 'timeout' => $config['write_timeout'], 'connect_timeout'=>$config['connect_timeout']))->send();
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
        if ($config['async'])
        {
            $proto = "udp";
        } else if ($config['secure']) {
             $proto = "ssl";
        } else {
            $proto = 'tcp';
        }

        return sprintf('%s://%s', $proto, $config['host']);
    }

    protected function addHttpHeadersIfNeeded($body, $config)
    {
        if($config['async']) {
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