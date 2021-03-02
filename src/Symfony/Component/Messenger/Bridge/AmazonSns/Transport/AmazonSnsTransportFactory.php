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

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AmazonSnsTransportFactory
{
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): SenderInterface
    {
        unset($options['transport_name']);

        return new AmazonSnsTransport(Connection::fromDsn($dsn, $options, null, $this->logger), $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'sns://') || preg_match('#^https://sns\.[\w\-]+\.amazonaws\.com/.+#', $dsn);
    }
}
