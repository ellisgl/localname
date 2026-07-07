<?php

declare(strict_types=1);

namespace Geeklab\Localname;

final class MacVendor
{
    /** @var array<string, string> OUI prefix => vendor name */
    private array $oui = [];

    private bool $loaded = false;

    public function __construct(
        private readonly string $dbPath,
    ) {}

    public static function defaultDbPath(): string
    {
        return dirname(__DIR__) . '/data/oui.csv';
    }

    public function lookup(string $mac): ?string
    {
        $this->load();

        $clean = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
        if (strlen($clean) < 6) {
            return null;
        }

        $prefix = substr($clean, 0, 6);

        return $this->oui[$prefix] ?? null;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (!is_file($this->dbPath)) {
            return;
        }

        $handle = @fopen($this->dbPath, 'r');
        if ($handle === false) {
            return;
        }

        $header = true;
        while (($row = fgetcsv($handle)) !== false) {
            if ($header) {
                $header = false;
                continue;
            }

            if (count($row) < 3) {
                continue;
            }

            $prefix = strtoupper(str_replace('-', '', $row[1]));
            if (strlen($prefix) === 6) {
                $this->oui[$prefix] = $row[2];
            }
        }

        fclose($handle);
    }
}