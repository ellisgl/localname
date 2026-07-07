<?php

declare(strict_types=1);

namespace Geeklab\Localname\Resolvers;

use Closure;
use Geeklab\Localname\ResolverInterface;

final class ReverseDns implements ResolverInterface
{
    /** @var Closure(string): string|false */
    private Closure $lookupFn;

    /**
     * @param (Closure(string): string|false)|null $lookupFn
     */
    public function __construct(?Closure $lookupFn = null)
    {
        $this->lookupFn = $lookupFn ?? gethostbyaddr(...);
    }

    public function getProtocol(): string
    {
        return 'reverse-dns';
    }

    public function resolve(string $ip): ?string
    {
        $host = ($this->lookupFn)($ip);

        if ($host === false || $host === $ip) {
            return null;
        }

        return $host;
    }
}
