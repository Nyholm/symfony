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

use AsyncAws\Sns\Enum\QueueAttributeName;
use AsyncAws\Sns\Exception\NotFoundException;
use AsyncAws\Sns\Result\ReceiveMessageResult;
use AsyncAws\Sns\SnsClient;
use AsyncAws\Sns\ValueObject\MessageAttributeValue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A SNS connection.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @internal
 * @final
 */
class Connection
{
    private const MESSAGE_ATTRIBUTE_NAME = 'X-Symfony-Messenger';

    private const DEFAULT_OPTIONS = [
        'auto_setup' => true,
        'access_key' => null,
        'secret_key' => null,
        'endpoint' => 'https://sns.eu-west-1.amazonaws.com',
        'region' => 'eu-west-1',
        'topic' => 'messages',
        'account' => null,
        'sslmode' => null,
        'debug' => null,
    ];

    private $configuration;
    private $client;

    public function __construct(array $configuration, SnsClient $client = null)
    {
        $this->configuration = array_replace_recursive(self::DEFAULT_OPTIONS, $configuration);
        $this->client = $client ?? new SnsClient([]);
    }

    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    /**
     * Creates a connection based on the DSN and options.
     *
     * Available options:
     *
     * * endpoint: absolute URL to the SNS service (Default: https://sns.eu-west-1.amazonaws.com)
     * * region: name of the AWS region (Default: eu-west-1)
     * * topic: name of the queue (Default: messages)
     * * account: identifier of the AWS account
     * * access_key: AWS access key
     * * secret_key: AWS secret key
     * * auto_setup: Whether the queue should be created automatically during send / get (Default: true)
     * * sslmode: Can be "disable" to use http for a custom endpoint
     * * debug: Log all HTTP requests and responses as LoggerInterface::DEBUG (Default: false)
     */
    public static function fromDsn(string $dsn, array $options = [], HttpClientInterface $client = null, LoggerInterface $logger = null): self
    {
        if (false === $parsedUrl = parse_url($dsn)) {
            throw new InvalidArgumentException(sprintf('The given Amazon SNS DSN "%s" is invalid.', $dsn));
        }

        $query = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        // check for extra keys in options
        $optionsExtraKeys = array_diff(array_keys($options), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($optionsExtraKeys)) {
            throw new InvalidArgumentException(sprintf('Unknown option found: [%s]. Allowed options are [%s].', implode(', ', $optionsExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }

        // check for extra keys in options
        $queryExtraKeys = array_diff(array_keys($query), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($queryExtraKeys)) {
            throw new InvalidArgumentException(sprintf('Unknown option found in DSN: [%s]. Allowed options are [%s].', implode(', ', $queryExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }

        $options = $query + $options + self::DEFAULT_OPTIONS;
        $configuration = [
            'auto_setup' => filter_var($options['auto_setup'], \FILTER_VALIDATE_BOOLEAN),
            'topic' => (string) $options['topic'],
        ];

        $clientConfiguration = [
            'region' => $options['region'],
            'accessKeyId' => urldecode($parsedUrl['user'] ?? '') ?: $options['access_key'] ?? self::DEFAULT_OPTIONS['access_key'],
            'accessKeySecret' => urldecode($parsedUrl['pass'] ?? '') ?: $options['secret_key'] ?? self::DEFAULT_OPTIONS['secret_key'],
        ];
        if (isset($options['debug'])) {
            $clientConfiguration['debug'] = $options['debug'];
        }
        unset($query['region']);

        if ('default' !== ($parsedUrl['host'] ?? 'default')) {
            $clientConfiguration['endpoint'] = sprintf('%s://%s%s', ($query['sslmode'] ?? null) === 'disable' ? 'http' : 'https', $parsedUrl['host'], ($parsedUrl['port'] ?? null) ? ':'.$parsedUrl['port'] : '');
            if (preg_match(';^sns\.([^\.]++)\.amazonaws\.com$;', $parsedUrl['host'], $matches)) {
                $clientConfiguration['region'] = $matches[1];
            }
        } elseif (self::DEFAULT_OPTIONS['endpoint'] !== $options['endpoint'] ?? self::DEFAULT_OPTIONS['endpoint']) {
            $clientConfiguration['endpoint'] = $options['endpoint'];
        }

        $parsedPath = explode('/', ltrim($parsedUrl['path'] ?? '/', '/'));
        if (\count($parsedPath) > 0 && !empty($topic = end($parsedPath))) {
            $configuration['topic'] = $topic;
        }
        $configuration['account'] = 2 === \count($parsedPath) ? $parsedPath[0] : $options['account'] ?? self::DEFAULT_OPTIONS['account'];

        return new self($configuration, new SnsClient($clientConfiguration, null, $client, $logger));
    }


    public function setup(): void
    {
        // Set to false to disable setup more than once
        $this->configuration['auto_setup'] = false;
        try {
            $this->client->getTopicAttributes(['name' => $this->configuration['topic'])->isSuccess());
        } catch (NotFoundException $e) {
            if (null !== $this->configuration['account']) {
                throw new InvalidArgumentException(sprintf('The Amazon SNS queue "%s" does not exists (or you don\'t have permissions on it), and can\'t be created when an account is provided.', $this->configuration['queue_name']));
            }

            $this->client->createTopic(['name' => $this->configuration['topic']]);
        }
    }

    public function send(string $body, array $headers, int $delay = 0, ?string $messageGroupId = null, ?string $messageDeduplicationId = null): void
    {
        if ($this->configuration['auto_setup']) {
            $this->setup();
        }

        $parameters = [
            'QueueUrl' => $this->getQueueUrl(),
            'MessageBody' => $body,
            'DelaySeconds' => $delay,
            'MessageAttributes' => [],
        ];

        $specialHeaders = [];
        foreach ($headers as $name => $value) {
            if ('.' === $name[0] || self::MESSAGE_ATTRIBUTE_NAME === $name || \strlen($name) > 256 || '.' === substr($name, -1) || 'AWS.' === substr($name, 0, \strlen('AWS.')) || 'Amazon.' === substr($name, 0, \strlen('Amazon.')) || preg_match('/([^a-zA-Z0-9_\.-]+|\.\.)/', $name)) {
                $specialHeaders[$name] = $value;

                continue;
            }

            $parameters['MessageAttributes'][$name] = new MessageAttributeValue([
                'DataType' => 'String',
                'StringValue' => $value,
            ]);
        }

        if (!empty($specialHeaders)) {
            $parameters['MessageAttributes'][self::MESSAGE_ATTRIBUTE_NAME] = new MessageAttributeValue([
                'DataType' => 'String',
                'StringValue' => json_encode($specialHeaders),
            ]);
        }

        $this->client->publish($parameters);
    }


    private function getQueueUrl(): string
    {
        if (null !== $this->queueUrl) {
            return $this->queueUrl;
        }

        return $this->queueUrl = $this->client->getQueueUrl([
            'QueueName' => $this->configuration['queue_name'],
            'QueueOwnerAWSAccountId' => $this->configuration['account'],
        ])->getQueueUrl();
    }

    private static function isFifoQueue(string $queueName): bool
    {
        return self::AWS_SNS_FIFO_SUFFIX === substr($queueName, -\strlen(self::AWS_SNS_FIFO_SUFFIX));
    }
}
