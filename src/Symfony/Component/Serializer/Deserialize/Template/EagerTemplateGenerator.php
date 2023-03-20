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

use Symfony\Component\Serializer\Deserialize\DataModel\CollectionNode;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelNodeInterface;
use Symfony\Component\Serializer\Deserialize\DataModel\ObjectNode;
use Symfony\Component\Serializer\Deserialize\DataModel\ScalarNode;
use Symfony\Component\Serializer\Deserialize\Decoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Php\ArrayNode;
use Symfony\Component\Serializer\Php\AssignNode;
use Symfony\Component\Serializer\Php\BinaryNode;
use Symfony\Component\Serializer\Php\CastNode;
use Symfony\Component\Serializer\Php\ClosureNode;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ForEachNode;
use Symfony\Component\Serializer\Php\FunctionCallNode;
use Symfony\Component\Serializer\Php\IfNode;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\NewNode;
use Symfony\Component\Serializer\Php\ParametersNode;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\ReturnNode;
use Symfony\Component\Serializer\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Serializer\Php\ThrowNode;
use Symfony\Component\Serializer\Php\TryCatchNode;
use Symfony\Component\Serializer\Php\VariableNode;
use Symfony\Component\Serializer\Php\YieldNode;

/**
 * Generates a template PHP syntax tree that deserializes data eagerly.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class EagerTemplateGenerator extends TemplateGenerator
{
    /**
     * @param class-string<DecoderInterface> $decoderClassName
     */
    public function __construct(
        private readonly string $decoderClassName,
    ) {
    }

    protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array
    {
        return [
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ArgumentsNode([
                    new MethodCallNode(new PhpScalarNode('\\'.$this->decoderClassName), 'decode', new ArgumentsNode([
                        new VariableNode('resource'),
                        new PhpScalarNode(0),
                        new PhpScalarNode(-1),
                        new VariableNode('config'),
                    ]), static: true),
                ]),
            ))),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function collectionNodes(CollectionNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $iterableClosureNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('iterable'),
                new ClosureNode(new ParametersNode(['data' => 'iterable']), 'iterable', true, [
                    new ForEachNode(new VariableNode('data'), new VariableNode('k'), new VariableNode('v'), [
                        new ExpressionNode(new YieldNode(
                            new FunctionCallNode(
                                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                                new ArgumentsNode([new VariableNode('v')]),
                            ),
                            new VariableNode('k'),
                        )),
                    ]),
                ], new ArgumentsNode([
                    new VariableNode('config'),
                    new VariableNode('instantiator'),
                    new VariableNode('providers', byReference: true),
                ])),
            )),
        ];

        $iterableValueNode = new FunctionCallNode(new VariableNode('iterable'), new ArgumentsNode([new VariableNode('data')]));

        $returnNodes = [
            new ExpressionNode(new ReturnNode(
                'array' === $node->type->name() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?iterable']),
                    ($node->type->isNullable() ? '?' : '').$node->type->name(),
                    true,
                    [...$returnNullNodes, ...$iterableClosureNodes, ...$returnNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$this->providerNodes($node->item, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function objectNodes(ObjectNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $propertyValueProvidersNodes = [];
        $fillPropertiesArrayNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('properties'), new ArrayNode([]))),
        ];

        foreach ($node->properties as $serializedName => $property) {
            array_push($propertyValueProvidersNodes, ...$this->providerNodes($property['value'], $context));

            $fillPropertiesArrayNodes[] = new IfNode(new FunctionCallNode(
                'isset',
                new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($serializedName))]),
            ), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(($property['formatter'])(new FunctionCallNode(
                            new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                            new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($serializedName))]),
                        )))),
                    ], new ArgumentsNode([
                        new VariableNode('data'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ])),
                )),
            ]);
        }

        $instantiateNodes = [
            new ExpressionNode(new ReturnNode(new MethodCallNode(
                new VariableNode('instantiator'),
                'instantiate',
                new ArgumentsNode([new PhpScalarNode($node->type->className()), new VariableNode('properties')]),
            ))),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?array']),
                    ($node->type->isNullable() ? '?' : '').$node->type->className(),
                    true,
                    [...$returnNullNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$propertyValueProvidersNodes,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function scalarNodes(ScalarNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $formatDataNodes = match (true) {
            \in_array($node->type->name(), ['int', 'string', 'float', 'bool', 'object', 'array'], true) => [
                new TryCatchNode([new ExpressionNode(new ReturnNode(new CastNode($node->type->name(), new VariableNode('data'))))], [
                    new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, new ArgumentsNode([
                        new FunctionCallNode('sprintf', new ArgumentsNode([
                            new PhpScalarNode(sprintf('Cannot cast "%%s" to "%s"', $node->type->name())),
                            new FunctionCallNode('get_debug_type', new ArgumentsNode([new VariableNode('data')])),
                        ])),
                    ])))),
                ], new ParametersNode(['e' => '\\Throwable'])),
            ],
            $node->type->isBackedEnum() => [
                new TryCatchNode([
                    new ExpressionNode(new ReturnNode(new MethodCallNode(
                        new PhpScalarNode($node->type->className()),
                        'from',
                        new ArgumentsNode([new VariableNode('data')]),
                        static: true,
                    ))),
                ], [
                    new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, new ArgumentsNode([
                        new FunctionCallNode('sprintf', new ArgumentsNode([
                            new PhpScalarNode(sprintf('Unexpected "%%s" value for "%s" backed enumeration.', $node->type)),
                            new VariableNode('data'),
                        ])),
                    ])))),
                ], new ParametersNode(['e' => '\\ValueError'])),
            ],
            default => [
                new ExpressionNode(new ReturnNode(new VariableNode('data'))),
            ],
        };

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [...$returnNullNodes, ...$formatDataNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
        ];
    }
}
