<?php

declare(strict_types=1);

namespace Geeklab\Localname\Resolvers;

use Geeklab\Localname\ResolverInterface;
use Geeklab\Localname\Transport\TransportInterface;
use Geeklab\Localname\Transport\UdpTransport;

final class Netbios implements ResolverInterface
{
    private const PORT = 137;

    private TransportInterface $transport;

    public function __construct(
        private readonly float $timeout = 2.0,
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new UdpTransport();
    }

    public function getProtocol(): string
    {
        return 'netbios';
    }

    public function resolve(string $ip): ?string
    {
        $id       = random_int(0, 0xFFFF);
        $query    = $this->buildNbstatQuery($id);
        $response = $this->transport->query($ip, self::PORT, $query, $this->timeout);

        if ($response === null) {
            return null;
        }

        return $this->parseNbstatResponse($response);
    }

    private function buildNbstatQuery(int $id): string
    {
        $header = pack('n6', $id, 0x0000, 1, 0, 0, 0);

        // Wildcard name "*" encoded as NetBIOS first-level encoding:
        // 0x20 (length 32) + "CKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA" + 0x00
        $name = "\x20CKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\x00";

        // NBSTAT query type = 0x0021, class IN = 0x0001
        $name .= pack('n2', 0x0021, 0x0001);

        return $header . $name;
    }

    private function parseNbstatResponse(string $data): ?string
    {
        if (strlen($data) < 12) {
            return null;
        }

        $header = unpack('nid/nflags/nqdcount/nancount/nnscount/narcount', $data);

        if (($header['flags'] & 0x8000) === 0) {
            return null;
        }

        if ($header['ancount'] === 0) {
            return null;
        }

        $offset = 12;
        for ($i = 0; $i < $header['qdcount']; $i++) {
            $offset = $this->skipNetbiosName($data, $offset);
            if ($offset === false) {
                return null;
            }

            $offset += 4;
        }

        $offset = $this->skipNetbiosName($data, $offset);
        if ($offset === false) {
            return null;
        }

        // Skip type(2) + class(2) + ttl(4) + rdlength(2)
        if (strlen($data) < $offset + 10) {
            return null;
        }

        $offset += 10;

        if (strlen($data) < $offset + 1) {
            return null;
        }

        $nameCount = ord($data[$offset]);
        $offset++;

        // Each name entry: 15 bytes name + 1 byte suffix + 2 bytes flags
        for ($i = 0; $i < $nameCount; $i++) {
            if (strlen($data) < $offset + 18) {
                return null;
            }

            $name   = substr($data, $offset, 15);
            $suffix = ord($data[$offset + 15]);
            $flags  = unpack('n', substr($data, $offset + 16, 2))[1];
            $offset += 18;

            // Suffix 0x00 = workstation, bit 15 (0x8000) = group flag
            if ($suffix === 0x00 && ($flags & 0x8000) === 0) {
                return rtrim($name);
            }
        }

        return null;
    }

    private function skipNetbiosName(string $data, int $offset): int|false
    {
        while ($offset < strlen($data)) {
            $len = ord($data[$offset]);

            if ($len === 0) {
                return $offset + 1;
            }

            if (($len & 0xC0) === 0xC0) {
                return $offset + 2;
            }

            $offset += $len + 1;
        }

        return false;
    }
}
