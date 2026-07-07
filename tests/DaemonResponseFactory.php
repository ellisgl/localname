<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests;

final class DaemonResponseFactory
{
    public static function ptrResponse(int $id, string $ip, string $hostname): string
    {
        $qname = self::ipToArpa($ip);

        // Header: response flag set, 1 question, 1 answer
        $header = pack('n6', $id, 0x8400, 1, 1, 0, 0);

        // Question section
        $question = self::encodeName($qname) . pack('n2', 12, 0x0001);

        // Answer section
        $answer = self::encodeName($qname);
        $rdata  = self::encodeName($hostname . '.');
        $answer .= pack('n2Nn', 12, 0x0001, 120, strlen($rdata));
        $answer .= $rdata;

        return $header . $question . $answer;
    }

    public static function ptrResponseNoAnswer(int $id, string $ip): string
    {
        $qname  = self::ipToArpa($ip);
        $header = pack('n6', $id, 0x8400, 1, 0, 0, 0);
        $question = self::encodeName($qname) . pack('n2', 12, 0x0001);

        return $header . $question;
    }

    public static function ptrResponseError(int $id, string $ip, int $rcode = 3): string
    {
        $qname  = self::ipToArpa($ip);
        $header = pack('n6', $id, 0x8400 | $rcode, 1, 0, 0, 0);
        $question = self::encodeName($qname) . pack('n2', 12, 0x0001);

        return $header . $question;
    }

    public static function nbstatResponse(int $id, string $computerName, string $workgroup = 'WORKGROUP'): string
    {
        // Header: response, 0 questions, 1 answer
        $header = pack('n6', $id, 0x8400, 0, 1, 0, 0);

        // Answer RR name (wildcard, same encoding as query)
        $rrName = "\x20CKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\x00";

        // Type NBSTAT (0x0021), class IN, TTL 0, rdlength placeholder
        $rrMeta = pack('n2N', 0x0021, 0x0001, 0);

        // Build RDATA: name count + name entries + MAC
        $nameCount = 2;

        // Entry 1: computer name (suffix 0x00, unique workstation)
        $entry1 = str_pad($computerName, 15, "\x00");
        $entry1 .= "\x00";         // suffix 0x00
        $entry1 .= pack('n', 0x0400); // flags: active, unique

        // Entry 2: workgroup (suffix 0x00, group)
        $entry2 = str_pad($workgroup, 15, "\x00");
        $entry2 .= "\x00";         // suffix 0x00
        $entry2 .= pack('n', 0x8400); // flags: active, group

        $mac = "\x00\x11\x22\x33\x44\x55";

        $rdata = chr($nameCount) . $entry1 . $entry2 . $mac;

        $rrMeta .= pack('n', strlen($rdata));

        return $header . $rrName . $rrMeta . $rdata;
    }

    public static function nbstatResponseEmpty(int $id): string
    {
        $header = pack('n6', $id, 0x8400, 0, 1, 0, 0);
        $rrName = "\x20CKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\x00";
        $rrMeta = pack('n2N', 0x0021, 0x0001, 0);
        $rdata  = chr(0);
        $rrMeta .= pack('n', strlen($rdata));

        return $header . $rrName . $rrMeta . $rdata;
    }

    public static function nbstatResponseNoAnswer(int $id): string
    {
        return pack('n6', $id, 0x8400, 0, 0, 0, 0);
    }

    private static function ipToArpa(string $ip): string
    {
        return implode('.', array_reverse(explode('.', $ip))) . '.in-addr.arpa';
    }

    private static function encodeName(string $name): string
    {
        $result = '';
        foreach (explode('.', rtrim($name, '.')) as $label) {
            $result .= chr(strlen($label)) . $label;
        }

        return $result . "\x00";
    }
}
