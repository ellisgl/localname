<?php

declare(strict_types=1);

namespace Geeklab\Localname\Transport;

final class UdpTransport implements TransportInterface
{
    public function query(string $host, int $port, string $data, float $timeout): ?string
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            return null;
        }

        try {
            $sec  = (int) $timeout;
            $usec = (int) (($timeout - $sec) * 1_000_000);
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
                'sec'  => $sec,
                'usec' => $usec,
            ]);

            socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
            @socket_bind($socket, '0.0.0.0', 0);
            @socket_sendto($socket, $data, strlen($data), 0, $host, $port);

            $buf  = '';
            $from = '';
            $rport = 0;
            $read = @socket_recvfrom($socket, $buf, 1024, 0, $from, $rport);

            if ($read === false || $read < 12) {
                return null;
            }

            return $buf;
        } finally {
            socket_close($socket);
        }
    }
}
