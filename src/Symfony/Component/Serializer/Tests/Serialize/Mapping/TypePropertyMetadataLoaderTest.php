<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Serialize\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

class TypePropertyMetadataLoaderTest extends TestCase
{
    public function testCastDateTimeToString()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::class(\DateTimeImmutable::class), []),
        ]), $this->createStub(TypeExtractorInterface::class));

        $metadata = $loader->load(self::class, new SerializeConfig(), ['original_type' => Type::fromString('useless')]);

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castDateTimeToString(...)),
            ]),
        ], $metadata);

        $formatter = $metadata['foo']->formatters()[0];

        $this->assertEquals(
            '2023-07-26T00:00:00+00:00',
            $formatter(new \DateTimeImmutable('2023-07-26'), new SerializeConfig()),
        );

        $this->assertEquals(
            '26/07/2023 00:00:00',
            $formatter((new \DateTimeImmutable('2023-07-26'))->setTime(0, 0), (new SerializeConfig())->withDateTimeFormat('d/m/Y H:i:s')),
        );
    }

    public function testReplaceGenerics()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            new SerializeConfig(),
            ['original_type' => Type::class(DummyWithGenerics::class, genericParameterTypes: [Type::int()])],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::int(), []),
        ], $metadata);
    }

    public function testReplaceGenericsAndCastDateTimeToString()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            new SerializeConfig(),
            ['original_type' => Type::class(DummyWithGenerics::class, genericParameterTypes: [Type::class(\DateTimeImmutable::class)])],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castDateTimeToString(...)),
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

            public function load(string $className, SerializeConfig $config, array $context): array
            {
                return $this->propertiesMetadata;
            }
        };
    }
}
