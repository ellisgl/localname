<?php

declare(strict_types=1);

namespace Geeklab\Localname;

final class Result
{
    public function __construct(
        public readonly string  $ip,
        public readonly ?string $name,
        public readonly ?string $protocol,
        public readonly ?string $mac    = null,
        public readonly ?string $vendor = null,
    ) {}
}