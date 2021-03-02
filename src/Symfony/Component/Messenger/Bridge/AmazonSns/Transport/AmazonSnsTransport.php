<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Bridge\AmazonSns\Transport;

use AsyncAws\Core\Exception\Http\HttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AmazonSnsTransport implements SenderInterface, SetupableTransportInterface
{
    private $serializer;
    private $connection;
    private $sender;

    public function __construct(Connection $connection, SerializerInterface $serializer = null, SenderInterface $sender = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        return ($this->sender ?? $this->getSender())->send($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        try {
            $this->connection->setup();
        } catch (HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    private function getSender(): AmazonSnsSender
    {
        return $this->sender = new AmazonSnsSender($this->connection, $this->serializer);
    }
}
