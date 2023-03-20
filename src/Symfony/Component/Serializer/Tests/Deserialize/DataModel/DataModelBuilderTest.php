<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\DataModel;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\DataModel\CollectionNode;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilder;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelNodeInterface;
use Symfony\Component\Serializer\Deserialize\DataModel\ObjectNode;
use Symfony\Component\Serializer\Deserialize\DataModel\ScalarNode;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\FunctionCallNode;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;
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

        $this->assertEquals($dataModel, $dataModelBuilder->build($type, new DeserializeConfig()));
    }

    /**
     * @return iterable<array{0: Type, 1: DataModelNodeInterface}>
     */
    public static function buildDataModelDataProvider(): iterable
    {
        yield [Type::int(), new ScalarNode(Type::int())];
        yield [Type::array(), new ScalarNode(Type::array())];
        yield [Type::object(), new ScalarNode(Type::object())];
        yield [Type::class(\stdClass::class), new ScalarNode(Type::object())];
        yield [Type::union(Type::int(), Type::string()), new ScalarNode(Type::union(Type::int(), Type::string()))];
        yield [Type::intersection(Type::int(), Type::string()), new ScalarNode(Type::intersection(Type::int(), Type::string()))];

        yield [Type::list(Type::string()), new CollectionNode(Type::list(Type::string()), new ScalarNode(Type::string()))];
        yield [Type::dict(Type::string()), new CollectionNode(Type::dict(Type::string()), new ScalarNode(Type::string()))];

        yield [Type::class(self::class), new ObjectNode(Type::class(self::class), [], false)];
    }

    public function testAddGhostLeafWhenClassAlreadyGenerated()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), self::runtimeServices());

        $this->assertEquals(new ObjectNode(Type::class(self::class), [[
            'name' => 'foo',
            'value' => new ObjectNode(Type::class(self::class), [], true),
            'formatter' => fn () => false,
        ]], false), $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig()));
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $config = new DeserializeConfig();
        $type = Type::class(self::class, true, [Type::int()]);

        $propertyMetadataLoader = $this->createMock(PropertyMetadataLoaderInterface::class);
        $propertyMetadataLoader->expects($this->once())
            ->method('load')
            ->with(self::class, $config, [
                'original_type' => $type,
                'generated_classes' => [(string) $type => true],
            ])
            ->willReturn([]);

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader, self::runtimeServices());
        $dataModelBuilder->build($type, $config);
    }

    public function testPropertyWithoutFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(new VariableNode('data'), $formatter(new VariableNode('data')));
    }

    public function testPropertyWithSimpleFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), ['strtoupper', DummyWithFormatterAttributes::divideAndCastToInt(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(
                sprintf('%s::divideAndCastToInt', DummyWithFormatterAttributes::class),
                new ArgumentsNode([new FunctionCallNode('strtoupper', new ArgumentsNode([new VariableNode('data')]))]),
            ),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithFormatterWithConfig()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithFormatterAttributes::class),
                [DummyWithFormatterAttributes::divideAndCastToIntWithConfig(...)],
            ),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::divideAndCastToIntWithConfig', DummyWithFormatterAttributes::class), new ArgumentsNode([
                new VariableNode('data'),
                new VariableNode('config'),
            ])),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithFormatterWithRuntimeServices()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndDeserializeConfig(...)],
            ),
        ]), self::runtimeServices([
            sprintf('%s::serviceAndDeserializeConfig[service]', DummyWithAttributesUsingServices::class) => 'useless',
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::serviceAndDeserializeConfig', DummyWithAttributesUsingServices::class), new ArgumentsNode([
                new VariableNode('data'),
                new MethodCallNode(
                    new VariableNode('services'),
                    'get',
                    new ArgumentsNode([new PhpScalarNode(sprintf('%s::serviceAndDeserializeConfig[service]', DummyWithAttributesUsingServices::class))]),
                ),
                new VariableNode('config'),
            ])),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithConstFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), [DummyWithMethods::const(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::const', DummyWithMethods::class), new ArgumentsNode([])),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithFormatterWithInvalidArgument()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndDeserializeConfig(...)],
            ),
        ]), self::runtimeServices());

        $this->expectException(LogicException::class);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new DeserializeConfig());
        $dataModel->properties[0]['formatter'](new VariableNode('data'));
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

            public function load(string $className, DeserializeConfig $config, array $context): array
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
