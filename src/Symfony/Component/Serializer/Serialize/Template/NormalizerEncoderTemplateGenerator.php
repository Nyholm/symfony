<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Php\ArrayNode;
use Symfony\Component\Serializer\Php\AssignNode;
use Symfony\Component\Serializer\Php\BinaryNode;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ForEachNode;
use Symfony\Component\Serializer\Php\IfNode;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\PropertyNode;
use Symfony\Component\Serializer\Php\ReturnNode;
use Symfony\Component\Serializer\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\CollectionNode;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelNodeInterface;
use Symfony\Component\Serializer\Serialize\DataModel\ObjectNode;
use Symfony\Component\Serializer\Serialize\DataModel\ScalarNode;
use Symfony\Component\Serializer\Serialize\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Serialize\VariableNameScoperTrait;

/**
 * Generates a template PHP syntax tree that serializes data by normalizing
 * it then encoding it using an $encoderClassName.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class NormalizerEncoderTemplateGenerator implements TemplateGeneratorInterface
{
    use VariableNameScoperTrait;

    /**
     * @param class-string<EncoderInterface> $encoderClassName
     */
    public function __construct(
        private readonly string $encoderClassName,
    ) {
    }

    public function generate(DataModelNodeInterface $node, SerializeConfig $config, array $context): array
    {
        $context['nested'] ??= false;
        $normalizedAccessor = $context['normalized_accessor'] ?? new VariableNode('normalized');

        $encodeNodes = !$context['nested'] ? [
            new ExpressionNode(new MethodCallNode(new PhpScalarNode('\\'.$this->encoderClassName), 'encode', new ArgumentsNode([
                new VariableNode('resource'),
                $normalizedAccessor,
                new VariableNode('config'),
            ]), static: true)),
        ] : [];

        $encodeNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new MethodCallNode(new PhpScalarNode('\\'.$this->encoderClassName), 'encode', new ArgumentsNode([
                    new VariableNode('resource'),
                    new PhpScalarNode(null),
                    new VariableNode('config'),
                ]), static: true)),
                new ExpressionNode(new ReturnNode(null)),
            ]),
        ] : [];

        if ($node instanceof CollectionNode) {
            $keyName = $this->scopeVariableName('key', $context);

            return [
                ...$encodeNullNodes,
                new ExpressionNode(new AssignNode($normalizedAccessor, new ArrayNode([]))),
                new ForEachNode($node->accessor, new VariableNode($keyName), $node->item->accessor, [
                    ...$this->generate($node->item, $config, [
                        'normalized_accessor' => new ArrayAccessNode($normalizedAccessor, new VariableNode($keyName)),
                        'nested' => true,
                    ] + $context),
                ]),
                ...$encodeNodes,
            ];
        }

        if ($node instanceof ObjectNode) {
            $nodes = [];

            foreach ($node->properties as $name => $propertyNode) {
                array_push(
                    $nodes,
                    ...$this->generate($propertyNode, $config, [
                        'normalized_accessor' => new ArrayAccessNode($normalizedAccessor, new PhpScalarNode($name)),
                        'nested' => true,
                    ] + $context),
                );
            }

            return [
                ...$encodeNullNodes,
                ...$nodes,
                ...$encodeNodes,
            ];
        }

        if ($node instanceof ScalarNode) {
            $scalarAccessor = $node->type->isBackedEnum() ? new PropertyNode($node->accessor, 'value') : $node->accessor;

            return [
                ...$encodeNullNodes,
                new ExpressionNode(new AssignNode($normalizedAccessor, $scalarAccessor)),
                ...$encodeNodes,
            ];
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $node::class));
    }
}
