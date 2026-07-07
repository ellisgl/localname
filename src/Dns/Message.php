<?php

declare(strict_types=1);

namespace Geeklab\Localname\Dns;

final class Message
{
    public static function buildPtrQuery(string $ip, int $id, bool $unicastResponse = false): string
    {
        $qname = self::ipToArpa($ip);

        $header = pack('n6', $id, 0x0000, 1, 0, 0, 0);

        $question = self::encodeName($qname);
        $qclass = $unicastResponse ? 0x8001 : 0x0001;
        $question .= pack('n2', 12, $qclass);

        return $header . $question;
    }

    public static function parsePtrResponse(string $data): ?string
    {
        if (strlen($data) < 12) {
            return null;
        }

        $header = unpack('nid/nflags/nqdcount/nancount/nnscount/narcount', $data);

        if (($header['flags'] & 0x8000) === 0) {
            return null;
        }

        if (($header['flags'] & 0x000F) !== 0) {
            return null;
        }

        if ($header['ancount'] === 0) {
            return null;
        }

        $offset = 12;

        for ($i = 0; $i < $header['qdcount']; $i++) {
            $offset = self::skipName($data, $offset);
            if ($offset === false) {
                return null;
            }

            $offset += 4;
        }

        $offset = self::skipName($data, $offset);
        if ($offset === false) {
            return null;
        }

        if (strlen($data) < $offset + 10) {
            return null;
        }

        $rr = unpack('ntype/nclass/Nttl/nrdlength', substr($data, $offset, 10));
        $offset += 10;

        if ($rr['type'] !== 12) {
            return null;
        }

        $name = self::decodeName($data, $offset);

        return $name !== null ? rtrim($name, '.') : null;
    }

    private static function ipToArpa(string $ip): string
    {
        return implode('.', array_reverse(explode('.', $ip))) . '.in-addr.arpa';
    }

    private static function encodeName(string $name): string
    {
        $result = '';
        foreach (explode('.', $name) as $label) {
            $result .= chr(strlen($label)) . $label;
        }

        return $result . "\x00";
    }

    private static function decodeName(string $data, int $offset): ?string
    {
        $labels = [];
        $jumps  = 0;

        while ($offset < strlen($data)) {
            $len = ord($data[$offset]);

            if ($len === 0) {
                break;
            }

            if (($len & 0xC0) === 0xC0) {
                if (++$jumps > 10) {
                    return null;
                }

                $offset = (($len & 0x3F) << 8) | ord($data[$offset + 1]);
                continue;
            }

            $offset++;
            $labels[] = substr($data, $offset, $len);
            $offset += $len;
        }

        return $labels === [] ? null : implode('.', $labels);
    }

    private static function skipName(string $data, int $offset): int|false
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