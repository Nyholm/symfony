<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Bridge\AmazonSns\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\AmazonSns\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Bridge\AmazonSns\Transport\AmazonSnsFifoStamp;
use Symfony\Component\Messenger\Bridge\AmazonSns\Transport\AmazonSnsSender;
use Symfony\Component\Messenger\Bridge\AmazonSns\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmazonSnsSenderTest extends TestCase
{
    public function testSend()
    {
        $envelope = new Envelope(new DummyMessage('Oy'));
        $encoded = ['body' => '...', 'headers' => ['type' => DummyMessage::class]];

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('send')->with($encoded['body'], $encoded['headers']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('encode')->with($envelope)->willReturnOnConsecutiveCalls($encoded);

        $sender = new AmazonSnsSender($connection, $serializer);
        $sender->send($envelope);
    }

    public function testSendWithAmazonSnsFifoStamp()
    {
        $envelope = (new Envelope(new DummyMessage('Oy')))
            ->with($stamp = new AmazonSnsFifoStamp('testGroup', 'testDeduplicationId'));

        $encoded = ['body' => '...', 'headers' => ['type' => DummyMessage::class]];

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('send')
            ->with($encoded['body'], $encoded['headers'], 0, $stamp->getMessageGroupId(), $stamp->getMessageDeduplicationId());

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('encode')->with($envelope)->willReturnOnConsecutiveCalls($encoded);

        $sender = new AmazonSnsSender($connection, $serializer);
        $sender->send($envelope);
    }
}
