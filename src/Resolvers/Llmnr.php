<?php

declare(strict_types=1);

namespace Geeklab\Localname\Resolvers;

use Geeklab\Localname\Dns\Message;
use Geeklab\Localname\ResolverInterface;
use Geeklab\Localname\Transport\TransportInterface;
use Geeklab\Localname\Transport\UdpTransport;

final class Llmnr implements ResolverInterface
{
    private const MULTICAST_ADDR = '224.0.0.252';
    private const PORT           = 5355;

    private TransportInterface $transport;

    public function __construct(
        private readonly float $timeout = 2.0,
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new UdpTransport();
    }

    public function getProtocol(): string
    {
        return 'llmnr';
    }

    public function resolve(string $ip): ?string
    {
        $id       = random_int(0, 0xFFFF);
        $query    = Message::buildPtrQuery($ip, $id);
        $response = $this->transport->query(self::MULTICAST_ADDR, self::PORT, $query, $this->timeout);

        if ($response === null) {
            return null;
        }

        return Message::parsePtrResponse($response);
    }
}
