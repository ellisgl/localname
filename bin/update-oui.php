#!/usr/bin/env php
<?php

declare(strict_types=1);

$dest = $argv[1] ?? dirname(__DIR__) . '/data/oui.csv';
$url  = 'https://standards-oui.ieee.org/oui/oui.csv';

if (in_array($dest, ['-h', '--help'], true)) {
    echo "Usage: update-oui [destination]\n";
    echo "  destination  Path to save oui.csv (default: <package>/data/oui.csv)\n";
    exit(0);
}

echo "Downloading OUI database from IEEE...\n";

$ctx = stream_context_create(['http' => ['timeout' => 30]]);
$csv = @file_get_contents($url, false, $ctx);

if ($csv === false) {
    fprintf(STDERR, "Failed to download %s\n", $url);
    exit(1);
}

file_put_contents($dest, $csv);
printf("Saved %s (%s bytes)\n", $dest, number_format(strlen($csv)));