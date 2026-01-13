<?php
declare(strict_types=1);

namespace Errbit\Writer;

interface WriterInterface
{
    /**
     * @param \Throwable $exception
     * @param array<string, mixed> $config
     *
     * @return mixed
     */
    public function write(\Throwable $exception, array $config): mixed;
}
