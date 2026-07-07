<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests\Resolvers;

use Geeklab\Localname\Resolvers\Llmnr;
use Geeklab\Localname\Tests\DaemonResponseFactory;
use Geeklab\Localname\Tests\MockTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LlmnrTest extends TestCase
{
    #[Test]
    public function resolvesHostnameFromLlmnrResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::ptrResponse(0, '192.168.1.20', 'desktop-win'),
        );

        $resolver = new Llmnr(transport: $transport);
        $this->assertSame('desktop-win', $resolver->resolve('192.168.1.20'));
    }

    #[Test]
    public function returnsNullWhenDaemonDoesNotRespond(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Llmnr(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.20'));
    }

    #[Test]
    public function returnsNullForEmptyAnswerResponse(): void
    {
        $transport = new MockTransport(
            DaemonResponseFactory::ptrResponseNoAnswer(0, '192.168.1.20'),
        );

        $resolver = new Llmnr(transport: $transport);
        $this->assertNull($resolver->resolve('192.168.1.20'));
    }

    #[Test]
    public function sendsToLlmnrMulticastAddress(): void
    {
        $transport = new MockTransport(null);

        $resolver = new Llmnr(transport: $transport);
        $resolver->resolve('192.168.1.20');

        $this->assertSame('224.0.0.252', $transport->lastHost);
        $this->assertSame(5355, $transport->lastPort);
    }

    #[Test]
    public function returnsCorrectProtocol(): void
    {
        $resolver = new Llmnr(transport: new MockTransport());
        $this->assertSame('llmnr', $resolver->getProtocol());
    }
}
