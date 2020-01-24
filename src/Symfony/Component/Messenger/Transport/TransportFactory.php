<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Transport;

use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @author Samuel Roze <samuel.roze@gmail.com>
 */
class TransportFactory implements TransportFactoryInterface
{
    private $factories;

    /**
     * @param iterable|TransportFactoryInterface[] $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($dsn, $options)) {
                return $factory->createTransport($dsn, $options, $serializer);
            }
        }

        // Help the user to select Symfony packages based on protocol.
        $packageSuggestion = '';
        if (substr($dsn, 0, 7) === 'amqp://') {
            $packageSuggestion = ' Run "composer require symfony/messenger-amqp" to install AMQP transport.';
        } elseif (substr($dsn, 0, 11) === 'doctrine://') {
            $packageSuggestion = ' Run "composer require symfony/messenger-doctrine" to install Doctrine transport.';
        } elseif (substr($dsn, 0, 8) === 'redis://') {
            $packageSuggestion = ' Run "composer require symfony/messenger-redis" to install Redis transport.';
        }

        throw new InvalidArgumentException(sprintf('No transport supports the given Messenger DSN "%s".%s', $dsn, $packageSuggestion));
    }

    public function supports(string $dsn, array $options): bool
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($dsn, $options)) {
                return true;
            }
        }

        return false;
    }
}
