<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize\DataModel;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MaxDepthException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\FunctionCallNode;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\PropertyNode;
use Symfony\Component\Serializer\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\CollectionNode;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilder;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelNodeInterface;
use Symfony\Component\Serializer\Serialize\DataModel\ObjectNode;
use Symfony\Component\Serializer\Serialize\DataModel\ScalarNode;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class DataModelBuilderTest extends TestCase
{
    /**
     * @dataProvider buildDataModelDataProvider
     */
    public function testBuildDataModel(Type $type, DataModelNodeInterface $dataModel)
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader(), self::runtimeServices());

        $this->assertEquals($dataModel, $dataModelBuilder->build($type, new VariableNode('data'), new SerializeConfig()));
    }

    /**
     * @return iterable<array{0: Type, 1: DataModelNodeInterface}>
     */
    public static function buildDataModelDataProvider(): iterable
    {
        $accessor = new VariableNode('data');

        yield [Type::int(), new ScalarNode($accessor, Type::int())];
        yield [Type::array(), new ScalarNode($accessor, Type::array())];
        yield [Type::object(), new ScalarNode($accessor, Type::object())];
        yield [Type::class(\stdClass::class), new ScalarNode($accessor, Type::object())];
        yield [Type::union(Type::int(), Type::string()), new ScalarNode($accessor, Type::union(Type::int(), Type::string()))];
        yield [Type::intersection(Type::int(), Type::string()), new ScalarNode($accessor, Type::intersection(Type::int(), Type::string()))];

        yield [Type::list(Type::string()), new CollectionNode($accessor, Type::list(Type::string()), new ScalarNode(new VariableNode('value_0'), Type::string()))];
        yield [Type::dict(Type::string()), new CollectionNode($accessor, Type::dict(Type::string()), new ScalarNode(new VariableNode('value_0'), Type::string()))];

        yield [Type::class(self::class), new ObjectNode($accessor, Type::class(self::class), [], false)];
    }

    public function testThrowWhenMaxDepthIsReached()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), self::runtimeServices());

        $this->expectException(MaxDepthException::class);
        $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $config = new SerializeConfig();
        $type = Type::class(self::class, true, [Type::int()]);

        $propertyMetadataLoader = $this->createMock(PropertyMetadataLoaderInterface::class);
        $propertyMetadataLoader->expects($this->once())
            ->method('load')
            ->with(self::class, $config, [
                'original_type' => $type,
                'depth_counters' => [$type->className() => 1],
            ])
            ->willReturn([]);

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader, self::runtimeServices());
        $dataModelBuilder->build($type, new VariableNode('data'), $config);
    }

    public function testPropertyWithSimpleAccessor()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), []),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());

        $this->assertEquals(new PropertyNode(new VariableNode('data'), 'foo'), $dataModel->properties[0]->accessor);
    }

    public function testPropertyWithCustomAccessors()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), ['strtoupper', DummyWithFormatterAttributes::doubleAndCastToString(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());

        $this->assertEquals(
            new FunctionCallNode(
                sprintf('%s::doubleAndCastToString', DummyWithFormatterAttributes::class),
                new ArgumentsNode([new FunctionCallNode('strtoupper', new ArgumentsNode([new PropertyNode(new VariableNode('data'), 'foo')]))]),
            ),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithAccessorWithConfig()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::int(),
                [DummyWithFormatterAttributes::doubleAndCastToStringWithConfig(...)],
            ),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::doubleAndCastToStringWithConfig', DummyWithFormatterAttributes::class), new ArgumentsNode([
                new PropertyNode(new VariableNode('data'), 'foo'),
                new VariableNode('config'),
            ])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithFormatterWithRuntimeServices()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::int(),
                [DummyWithAttributesUsingServices::serviceAndSerializeConfig(...)],
            ),
        ]), self::runtimeServices([
            sprintf('%s::serviceAndSerializeConfig[serializer]', DummyWithAttributesUsingServices::class) => 'useless',
            sprintf('%s::serviceAndSerializeConfig[typeExtractor]', DummyWithAttributesUsingServices::class) => 'useless',
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::serviceAndSerializeConfig', DummyWithAttributesUsingServices::class), new ArgumentsNode([
                new PropertyNode(new VariableNode('data'), 'foo'),
                new MethodCallNode(
                    new VariableNode('services'),
                    'get',
                    new ArgumentsNode([new PhpScalarNode(sprintf('%s::serviceAndSerializeConfig[serializer]', DummyWithAttributesUsingServices::class))]),
                ),
                new MethodCallNode(
                    new VariableNode('services'),
                    'get',
                    new ArgumentsNode([new PhpScalarNode(sprintf('%s::serviceAndSerializeConfig[typeExtractor]', DummyWithAttributesUsingServices::class))]),
                ),
                new VariableNode('config'),
            ])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithConstAccessor()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), [DummyWithMethods::const(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::const', DummyWithMethods::class), new ArgumentsNode([])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithFormatterWithInvalidArgument()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndSerializeConfig(...)],
            ),
        ]), self::runtimeServices());

        $this->expectException(LogicException::class);

        $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), new SerializeConfig());
    }

    /**
     * @param array<string, PropertyMetadata> $propertiesMetadata
     */
    private static function propertyMetadataLoader(array $propertiesMetadata = []): PropertyMetadataLoaderInterface
    {
        return new class($propertiesMetadata) implements PropertyMetadataLoaderInterface {
            public function __construct(private readonly array $propertiesMetadata)
            {
            }

            public function load(string $className, SerializeConfig $config, array $context): array
            {
                return $this->propertiesMetadata;
            }
        };
    }

    /**
     * @param array<string, mixed> $runtimeServices
     */
    private static function runtimeServices(array $runtimeServices = []): ContainerInterface
    {
        return new class($runtimeServices) implements ContainerInterface {
            use ServiceLocatorTrait;
        };
    }
}
