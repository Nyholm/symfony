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
use Symfony\Component\Serializer\Deserialize\Splitter\SplitterInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Php\ArrayNode;
use Symfony\Component\Serializer\Php\AssignNode;
use Symfony\Component\Serializer\Php\BinaryNode;
use Symfony\Component\Serializer\Php\CastNode;
use Symfony\Component\Serializer\Php\ClosureNode;
use Symfony\Component\Serializer\Php\ContinueNode;
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
 * Generates a template PHP syntax tree that deserializes data lazily.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class LazyTemplateGenerator extends TemplateGenerator
{
    /**
     * @param class-string<DecoderInterface>  $decoderClassName
     * @param class-string<SplitterInterface> $splitterClassName
     */
    public function __construct(
        private readonly string $decoderClassName,
        private readonly string $splitterClassName,
    ) {
    }

    protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array
    {
        return [
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode(0), new PhpScalarNode(-1)]),
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
        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.$this->splitterClassName),
                $node->type->isList() ? 'splitList' : 'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->type->isNullable()) {
            $getBoundariesNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('boundaries')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

        $iterableClosureNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('iterable'),
                new ClosureNode(new ParametersNode(['resource' => 'mixed', 'boundaries' => 'iterable']), 'iterable', true, [
                    new ForEachNode(new VariableNode('boundaries'), new VariableNode('k'), new VariableNode('b'), [
                        new ExpressionNode(new YieldNode(
                            new FunctionCallNode(
                                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                                new ArgumentsNode([
                                    new VariableNode('resource'),
                                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                                ]),
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

        $iterableValueNode = new FunctionCallNode(
            new VariableNode('iterable'),
            new ArgumentsNode([new VariableNode('resource'), new VariableNode('boundaries')]),
        );

        $returnNodes = [
            new ExpressionNode(new ReturnNode(
                'array' === $node->type->name() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->type->isNullable() ? '?' : '').$node->type->name(),
                    true,
                    [...$getBoundariesNodes, ...$iterableClosureNodes, ...$returnNodes],
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
        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.$this->splitterClassName),
                'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->type->isNullable()) {
            $getBoundariesNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('boundaries')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

        $propertyValueProvidersNodes = [];
        $propertiesClosuresNodes = [];

        foreach ($node->properties as $serializedName => $property) {
            array_push($propertyValueProvidersNodes, ...$this->providerNodes($property['value'], $context));

            $propertiesClosuresNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode($serializedName), new VariableNode('k')), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(($property['formatter'])(new FunctionCallNode(
                            new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                            new ArgumentsNode([
                                new VariableNode('resource'),
                                new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                                new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                            ]),
                        )))),
                    ], new ArgumentsNode([
                        new VariableNode('resource'),
                        new VariableNode('b'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ])),
                )),
                new ExpressionNode(new ContinueNode()),
            ]);
        }

        $fillPropertiesArrayNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('properties'), new ArrayNode([]))),
            new ForEachNode(new VariableNode('boundaries'), new VariableNode('k'), new VariableNode('b'), $propertiesClosuresNodes),
        ];

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
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->type->isNullable() ? '?' : '').$node->type->className(),
                    true,
                    [...$getBoundariesNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
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
        $getDataNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('data'),
                new MethodCallNode(new PhpScalarNode('\\'.$this->decoderClassName), 'decode', new ArgumentsNode([
                    new VariableNode('resource'),
                    new VariableNode('offset'),
                    new VariableNode('length'),
                    new VariableNode('config'),
                ]), static: true),
            )),
        ];

        if ($node->type->isNullable()) {
            $getDataNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

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
            $node->type->isEnum() => [
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
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    'mixed',
                    true,
                    [...$getDataNodes, ...$formatDataNodes],
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
