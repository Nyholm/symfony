<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Deserialize\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

class TypePropertyMetadataLoaderTest extends TestCase
{
    public function testCastStringToDateTime()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::class(\DateTimeImmutable::class), []),
        ]), $this->createStub(TypeExtractorInterface::class));

        $metadata = $loader->load(self::class, new DeserializeConfig(), ['original_type' => Type::fromString('useless')]);

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castStringToDateTime(...)),
            ]),
        ], $metadata);

        $formatter = $metadata['foo']->formatters()[0];

        $this->assertEquals(
            new \DateTimeImmutable('2023-07-26'),
            $formatter('2023-07-26', new DeserializeConfig()),
        );

        $this->assertEquals(
            (new \DateTimeImmutable('2023-07-26'))->setTime(0, 0),
            $formatter('26/07/2023 00:00:00', (new DeserializeConfig())->withDateTimeFormat('d/m/Y H:i:s')),
        );
    }

    public function testReplaceGenerics()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            new DeserializeConfig(),
            ['original_type' => Type::class(DummyWithGenerics::class, genericParameterTypes: [Type::int()])],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::int(), []),
        ], $metadata);
    }

    public function testReplaceGenericsAndCastStringToDateTime()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            new DeserializeConfig(),
            ['original_type' => Type::class(DummyWithGenerics::class, genericParameterTypes: [Type::class(\DateTimeImmutable::class)])],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castStringToDateTime(...)),
            ]),
        ], $metadata);
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
}
