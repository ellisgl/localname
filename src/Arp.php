<?php

declare(strict_types=1);

namespace Geeklab\Localname;

final class Arp
{
    public static function getMac(string $ip): ?string
    {
        $arp = @file('/proc/net/arp', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($arp === false) {
            return self::getMacFromArpCommand($ip);
        }

        foreach ($arp as $line) {
            $fields = preg_split('/\s+/', $line);
            if ($fields === false || count($fields) < 4) {
                continue;
            }

            if ($fields[0] === $ip && $fields[3] !== '00:00:00:00:00:00') {
                return strtoupper($fields[3]);
            }
        }

        return null;
    }

    private static function getMacFromArpCommand(string $ip): ?string
    {
        if (!preg_match('/\A\d{1,3}(\.\d{1,3}){3}\z/', $ip)) {
            return null;
        }

        $output = @shell_exec('arp -n ' . escapeshellarg($ip) . ' 2>/dev/null');
        if ($output === null || $output === false) {
            return null;
        }

        if (preg_match('/([0-9a-f]{2}(?::[0-9a-f]{2}){5})/i', $output, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }
}