<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests;

use Geeklab\Localname\Transport\TransportInterface;

final class MockTransport implements TransportInterface
{
    private ?string $response;
    public ?string $lastHost = null;
    public ?int $lastPort    = null;
    public ?string $lastData = null;

    public function __construct(?string $response = null)
    {
        $this->response = $response;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    public function query(string $host, int $port, string $data, float $timeout): ?string
    {
        $this->lastHost = $host;
        $this->lastPort = $port;
        $this->lastData = $data;

        return $this->response;
    }
}
