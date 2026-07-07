<?php

declare(strict_types=1);

namespace Geeklab\Localname;

interface ResolverInterface
{
    public function getProtocol(): string;

    public function resolve(string $ip): ?string;
}