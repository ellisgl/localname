<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests;

use Geeklab\Localname\LocalName;
use Geeklab\Localname\Resolvers\Llmnr;
use Geeklab\Localname\Resolvers\Mdns;
use Geeklab\Localname\Resolvers\Netbios;
use Geeklab\Localname\Resolvers\ReverseDns;
use Geeklab\Localname\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LocalNameTest extends TestCase
{
    #[Test]
    public function lookupReturnsFirstMatch(): void
    {
        $mdnsTransport    = new MockTransport(null);
        $netbiosTransport = new MockTransport(
            DaemonResponseFactory::nbstatResponse(0, 'WINBOX'),
        );

        $ln = new LocalName(
            null,
            new Mdns(transport: $mdnsTransport),
            new Netbios(transport: $netbiosTransport),
        );

        $result = $ln->lookup('192.168.1.5');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame('WINBOX', $result->name);
        $this->assertSame('netbios', $result->protocol);
        $this->assertSame('192.168.1.5', $result->ip);
    }

    #[Test]
    public function lookupReturnsNoNameWhenNothingResolves(): void
    {
        $ln = new LocalName(
            null,
            new Mdns(transport: new MockTransport(null)),
            new Netbios(transport: new MockTransport(null)),
            new ReverseDns(fn(string $ip) => $ip),
        );

        $result = $ln->lookup('192.168.1.5');
        $this->assertNull($result->name);
    }

    #[Test]
    public function lookupRespectsResolverOrder(): void
    {
        $mdnsTransport = new MockTransport(
            DaemonResponseFactory::ptrResponse(0, '192.168.1.5', 'from-mdns.local'),
        );
        $netbiosTransport = new MockTransport(
            DaemonResponseFactory::nbstatResponse(0, 'FROM-NETBIOS'),
        );

        // mDNS first — should win
        $ln = new LocalName(
            null,
            new Mdns(transport: $mdnsTransport),
            new Netbios(transport: $netbiosTransport),
        );

        $result = $ln->lookup('192.168.1.5');
        $this->assertSame('mdns', $result->protocol);
        $this->assertSame('from-mdns.local', $result->name);
    }

    #[Test]
    public function lookupAllReturnsAllMatches(): void
    {
        $mdnsTransport = new MockTransport(
            DaemonResponseFactory::ptrResponse(0, '192.168.1.5', 'host.local'),
        );
        $netbiosTransport = new MockTransport(
            DaemonResponseFactory::nbstatResponse(0, 'HOSTPC'),
        );

        $ln = new LocalName(
            null,
            new Mdns(transport: $mdnsTransport),
            new Netbios(transport: $netbiosTransport),
            new ReverseDns(fn(string $ip) => 'host.example.com'),
        );

        $results = $ln->lookupAll('192.168.1.5');

        $this->assertCount(3, $results);
        $this->assertSame('mdns', $results[0]->protocol);
        $this->assertSame('netbios', $results[1]->protocol);
        $this->assertSame('reverse-dns', $results[2]->protocol);
    }

    #[Test]
    public function lookupAllReturnsEmptyWhenNothingResolves(): void
    {
        $ln = new LocalName(
            null,
            new Mdns(transport: new MockTransport(null)),
            new ReverseDns(fn(string $ip) => false),
        );

        $this->assertSame([], $ln->lookupAll('192.168.1.5'));
    }

    #[Test]
    public function createReturnsInstanceWithAllResolvers(): void
    {
        $ln = LocalName::create();
        $this->assertInstanceOf(LocalName::class, $ln);
    }
}
