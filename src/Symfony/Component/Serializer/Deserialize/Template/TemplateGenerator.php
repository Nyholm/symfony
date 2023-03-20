<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Template;

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\DataModel\CollectionNode;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelNodeInterface;
use Symfony\Component\Serializer\Deserialize\DataModel\ObjectNode;
use Symfony\Component\Serializer\Deserialize\DataModel\ScalarNode;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Php\PhpNodeInterface;

/**
 * A base class to generate a deserialization template PHP syntax tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract class TemplateGenerator implements TemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function collectionNodes(CollectionNode $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function objectNodes(ObjectNode $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function scalarNodes(ScalarNode $node, array &$context): array;

    final public function generate(DataModelNodeInterface $node, DeserializeConfig $config, array $context): array
    {
        return [
            ...$this->providerNodes($node, $context),
            ...$this->returnDataNodes($node, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    final protected function providerNodes(DataModelNodeInterface $node, array &$context): array
    {
        if ($context['providers'][$node->identifier()] ?? false) {
            return [];
        }

        if ($node instanceof ObjectNode && $node->ghost) {
            return [];
        }

        $context['providers'][$node->identifier()] = true;

        return match (true) {
            $node instanceof CollectionNode => $this->collectionNodes($node, $context),
            $node instanceof ObjectNode => $this->objectNodes($node, $context),
            $node instanceof ScalarNode => $this->scalarNodes($node, $context),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }
}
