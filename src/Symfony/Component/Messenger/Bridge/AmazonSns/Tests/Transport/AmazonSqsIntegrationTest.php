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

use AsyncAws\Sns\SnsClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\AmazonSns\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Bridge\AmazonSns\Transport\Connection;

/**
 * @group integration
 */
class AmazonSnsIntegrationTest extends TestCase
{
    public function testConnectionSendToFifoQueueAndGet()
    {
        if (!getenv('MESSENGER_SNS_FIFO_QUEUE_DSN')) {
            $this->markTestSkipped('The "MESSENGER_SNS_FIFO_QUEUE_DSN" environment variable is required.');
        }

        $this->execute(getenv('MESSENGER_SNS_FIFO_QUEUE_DSN'));
    }

    public function testConnectionSendAndGet()
    {
        if (!getenv('MESSENGER_SNS_DSN')) {
            $this->markTestSkipped('The "MESSENGER_SNS_DSN" environment variable is required.');
        }

        $this->execute(getenv('MESSENGER_SNS_DSN'));
    }

    private function execute(string $dsn): void
    {
        $connection = Connection::fromDsn($dsn, []);
        $connection->setup();
        $this->clearSns($dsn);

        $connection->send('{"message": "Hi"}', ['type' => DummyMessage::class, DummyMessage::class => 'special']);
        $this->assertSame(1, $connection->getMessageCount());

        $wait = 0;
        while ((null === $encoded = $connection->get()) && $wait++ < 200) {
            usleep(5000);
        }

        $this->assertEquals('{"message": "Hi"}', $encoded['body']);
        $this->assertEquals(['type' => DummyMessage::class, DummyMessage::class => 'special'], $encoded['headers']);
    }

    private function clearSns(string $dsn): void
    {
        $url = parse_url($dsn);
        $client = new SnsClient(['endpoint' => "http://{$url['host']}:{$url['port']}"]);
        $client->purgeQueue([
            'QueueUrl' => $client->getQueueUrl(['QueueName' => ltrim($url['path'], '/')])->getQueueUrl(),
        ]);
    }
}
