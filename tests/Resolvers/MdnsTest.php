<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests\Resolvers;

use Geeklab\Localname\Resolvers\Mdns;
use Geeklab\Localname\Tests\DaemonResponseFactory;
use Geeklab\Localname\Tests\MockTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MdnsTest extends TestCase
{
    #[Test]
    public function resolvesHostnameFromMdnsResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::ptrResponse(0, '192.168.1.10', 'macbook.local'),
        );

        $resolver = new Mdns(transport: $transport);
        $this->assertSame('macbook.local', $resolver->resolve('192.168.1.10'));
    }

    #[Test]
    public function returnsNullWhenDaemonDoesNotRespond(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Mdns(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.10'));
    }

    #[Test]
    public function returnsNullForEmptyAnswerResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::ptrResponseNoAnswer(0, '192.168.1.10'),
        );

        $resolver = new Mdns(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.10'));
    }

    #[Test]
    public function sendsToMdnsMulticastAddress(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Mdns(transport: $transport);
        $resolver->resolve('192.168.1.10');

        $this->assertSame('224.0.0.251', $transport->lastHost);
        $this->assertSame(5353, $transport->lastPort);
    }

    #[Test]
    public function returnsCorrectProtocol(): void
    {
        $resolver = new Mdns(transport: new MockTransport());
        $this->assertSame('mdns', $resolver->getProtocol());
    }

    #[Test]
    public function returnsNullForErrorResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::ptrResponseError(0, '192.168.1.10'),
        );

        $resolver = new Mdns(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.10'));
    }
}
