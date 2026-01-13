<?php
declare(strict_types=1);
namespace Errbit\Writer;

use Errbit\Exception\Notice;

abstract class AbstractWriter
{
    /**
     * Hoptoad Notifier Route
     */
    public const NOTICES_PATH  = '/notifier_api/v2/notices/';
    /**
     * @param array<string, mixed> $config
     * @return string
     */
    protected function buildConnectionScheme(array $config): string
    {
        $proto = "";
        if ($config['async']) {
            $proto = "udp";
        } elseif ($config['secure']) {
            $proto = "ssl";
        } else {
            $proto = 'tcp';
        }

        return sprintf('%s://%s', $proto, (string) $config['host']);
    }
    
    /**
     * @param \Throwable $exception
     * @param array<string, mixed> $options
     *
     * @return string
     */
    protected function buildNoticeFor(\Throwable $exception, array $options): string
    {
        return Notice::forException($exception, $options)->asXml();
    }
    
}
