<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Exception\Notice;

abstract class AbstractWriter
{
    /**
     * Hoptoad Notifier Route
     */
    final const NOTICES_PATH  = '/notifier_api/v2/notices/';
    /**
     * @return string
     */
    protected function buildConnectionScheme(array $config)
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
    
    /**
     * @param $exception
     * @param $options
     *
     * @return string
     */
    protected function buildNoticeFor($exception, $options)
    {
        return Notice::forException($exception, $options)->asXml();
    }
    
}
