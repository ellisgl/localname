<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests\Resolvers;

use Geeklab\Localname\Resolvers\ReverseDns;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReverseDnsTest extends TestCase
{
    #[Test]
    public function resolvesHostnameFromSystemResolver(): void
    {
        $resolver = new ReverseDns(fn(string $ip) => 'router.home');

        $this->assertSame('router.home', $resolver->resolve('192.168.1.1'));
    }

    #[Test]
    public function returnsNullWhenResolverReturnsIp(): void
    {
        $resolver = new ReverseDns(fn(string $ip) => $ip);

        $this->assertNull($resolver->resolve('192.168.1.99'));
    }

    #[Test]
    public function returnsNullWhenResolverReturnsFalse(): void
    {
        $resolver = new ReverseDns(fn(string $ip) => false);

        $this->assertNull($resolver->resolve('192.168.1.99'));
    }

    #[Test]
    public function passesIpToLookupFunction(): void
    {
        $receivedIp = null;
        $resolver   = new ReverseDns(function (string $ip) use (&$receivedIp) {
            $receivedIp = $ip;

            return false;
        });

        $resolver->resolve('10.0.0.42');
        $this->assertSame('10.0.0.42', $receivedIp);
    }

    #[Test]
    public function returnsCorrectProtocol(): void
    {
        $resolver = new ReverseDns(fn(string $ip) => false);
        $this->assertSame('reverse-dns', $resolver->getProtocol());
    }
}
