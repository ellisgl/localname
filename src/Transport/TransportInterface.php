<?php

declare(strict_types=1);

namespace Geeklab\Localname\Transport;

interface TransportInterface
{
    public function query(string $host, int $port, string $data, float $timeout): ?string;
}
