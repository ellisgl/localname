<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests\Resolvers;

use Geeklab\Localname\Resolvers\Netbios;
use Geeklab\Localname\Tests\DaemonResponseFactory;
use Geeklab\Localname\Tests\MockTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NetbiosTest extends TestCase
{
    #[Test]
    public function resolvesComputerNameFromNbstatResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::nbstatResponse(0, 'FILESERVER'),
        );

        $resolver = new Netbios(transport: $transport);
        $this->assertSame('FILESERVER', $resolver->resolve('192.168.1.30'));
    }

    #[Test]
    public function returnsNullWhenDaemonDoesNotRespond(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Netbios(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.30'));
    }

    #[Test]
    public function returnsNullForNoAnswerResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::nbstatResponseNoAnswer(0),
        );

        $resolver = new Netbios(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.30'));
    }

    #[Test]
    public function returnsNullForEmptyNameTable(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::nbstatResponseEmpty(0),
        );

        $resolver = new Netbios(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.30'));
    }

    #[Test]
    public function sendsQueryDirectlyToTargetIp(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Netbios(transport: $transport);
        $resolver->resolve('10.0.0.5');

        $this->assertSame('10.0.0.5', $transport->lastHost);
        $this->assertSame(137, $transport->lastPort);
    }

    #[Test]
    public function queryContainsWildcardName(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Netbios(transport: $transport);
        $resolver->resolve('10.0.0.5');

        $this->assertStringContainsString('CKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $transport->lastData);
    }

    #[Test]
    public function returnsCorrectProtocol(): void
    {
        $resolver = new Netbios(transport: new MockTransport());
        $this->assertSame('netbios', $resolver->getProtocol());
    }

    #[Test]
    public function skipsGroupNamesAndReturnsUniqueWorkstation(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::nbstatResponse(0, 'MYPC', 'OFFICE'),
        );

        $resolver = new Netbios(transport: $transport);
        $this->assertSame('MYPC', $resolver->resolve('192.168.1.30'));
    }
}
