<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

use Geeklab\Localname\LocalName;

$ip       = $argv[1] ?? '192.168.12.45';
$resolver = LocalName::create();

$result = $resolver->lookup($ip);

printf("IP:       %s\n", $result->ip);
printf("MAC:      %s\n", $result->mac ?? 'unknown');
printf("Vendor:   %s\n", $result->vendor ?? 'unknown');
printf("Name:     %s\n", $result->name ?? 'not found');
printf("Protocol: %s\n", $result->protocol ?? 'n/a');
