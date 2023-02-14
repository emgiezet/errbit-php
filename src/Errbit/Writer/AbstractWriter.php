<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Errors\ErrorInterface;
use Errbit\Exception\Notice;

abstract class AbstractWriter
{
    /**
     * Hoptoad Notifier Route
     */
    public const NOTICES_PATH  = '/notifier_api/v2/notices/';
    
    /**
     * @param array $config
     *
     * @return string
     */
    protected function buildConnectionScheme(array $config): string
    {
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
     * @param \Errbit\Errors\ErrorInterface $exception
     * @param array $options
     *
     * @return string
     */
    protected function buildNoticeFor(ErrorInterface $exception, array $options): string
    {
        return Notice::forException($exception, $options)->asXml();
    }
    
}
