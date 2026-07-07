<?php

declare(strict_types=1);

namespace Geeklab\Localname\Tests\Dns;

use Geeklab\Localname\Dns\Message;
use Geeklab\Localname\Tests\DaemonResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    #[Test]
    public function buildPtrQueryContainsReversedIp(): void
    {
        $query = Message::buildPtrQuery('192.168.1.42', 0x1234);

        $this->assertStringContainsString('42', $query);
        $this->assertStringContainsString('1', $query);
        $this->assertStringContainsString('168', $query);
        $this->assertStringContainsString('192', $query);
        $this->assertStringContainsString('in-addr', $query);
        $this->assertStringContainsString('arpa', $query);
    }

    #[Test]
    public function buildPtrQuerySetsTransactionId(): void
    {
        $query = Message::buildPtrQuery('10.0.0.1', 0xABCD);

        $id = unpack('n', substr($query, 0, 2))[1];
        $this->assertSame(0xABCD, $id);
    }

    #[Test]
    public function buildPtrQueryUnicastSetsQuBit(): void
    {
        $query = Message::buildPtrQuery('10.0.0.1', 0, unicastResponse: true);

        // QU bit is in QCLASS, the last 2 bytes of the packet
        $qclass = unpack('n', substr($query, -2))[1];
        $this->assertSame(0x8001, $qclass);
    }

    #[Test]
    public function buildPtrQueryStandardHasNoQuBit(): void
    {
        $query = Message::buildPtrQuery('10.0.0.1', 0, unicastResponse: false);

        $qclass = unpack('n', substr($query, -2))[1];
        $this->assertSame(0x0001, $qclass);
    }

    #[Test]
    public function parsePtrResponseExtractsHostname(): void
    {
        $response = DaemonResponseFactory::ptrResponse(0x1234, '192.168.1.42', 'myhost.local');

        $name = Message::parsePtrResponse($response);
        $this->assertSame('myhost.local', $name);
    }

    #[Test]
    public function parsePtrResponseReturnsNullForNoAnswers(): void
    {
        $response = DaemonResponseFactory::ptrResponseNoAnswer(0x1234, '192.168.1.42');

        $this->assertNull(Message::parsePtrResponse($response));
    }

    #[Test]
    public function parsePtrResponseReturnsNullForErrorResponse(): void
    {
        $response = DaemonResponseFactory::ptrResponseError(0x1234, '192.168.1.42', rcode: 3);

        $this->assertNull(Message::parsePtrResponse($response));
    }

    #[Test]
    public function parsePtrResponseReturnsNullForTruncatedData(): void
    {
        $this->assertNull(Message::parsePtrResponse('short'));
    }

    #[Test]
    public function parsePtrResponseReturnsNullForQueryPacket(): void
    {
        // A query has the response bit (0x8000) unset
        $query = Message::buildPtrQuery('10.0.0.1', 0x0001);
        $this->assertNull(Message::parsePtrResponse($query));
    }

    #[Test]
    public function parsePtrResponseHandlesSimpleHostname(): void
    {
        $response = DaemonResponseFactory::ptrResponse(0, '10.0.0.1', 'server');

        $this->assertSame('server', Message::parsePtrResponse($response));
    }

    #[Test]
    public function parsePtrResponseHandlesDeepSubdomain(): void
    {
        $response = DaemonResponseFactory::ptrResponse(0, '10.0.0.1', 'a.b.c.d.example.com');

        $this->assertSame('a.b.c.d.example.com', Message::parsePtrResponse($response));
    }
}
