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
use Symfony\Component\Serializer\Serialize\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithMaxDepthAttribute;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;

class AttributePropertyMetadataLoaderTest extends TestCase
{
    public function testFilterPropertiesByGroups()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertSame(
            ['none', 'one', 'oneAndTwo', 'twoAndThree'],
            array_keys($loader->load(DummyWithGroups::class, new SerializeConfig(), [])),
        );

        $this->assertSame(
            ['one', 'oneAndTwo'],
            array_keys($loader->load(DummyWithGroups::class, (new SerializeConfig())->withGroups(['one']), [])),
        );

        $this->assertSame(
            ['oneAndTwo', 'twoAndThree'],
            array_keys($loader->load(DummyWithGroups::class, (new SerializeConfig())->withGroups(['two']), [])),
        );

        $this->assertSame(
            ['twoAndThree'],
            array_keys($loader->load(DummyWithGroups::class, (new SerializeConfig())->withGroups(['three']), [])),
        );

        $this->assertSame([], $loader->load(DummyWithGroups::class, (new SerializeConfig())->withGroups(['other']), []));
    }

    public function testRetrieveSerializedName()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertSame(['@id', 'name'], array_keys($loader->load(DummyWithNameAttributes::class, new SerializeConfig(), [])));
    }

    public function testRetrieveSerializeFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::string(), [DummyWithFormatterAttributes::doubleAndCastToString(...)]),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(DummyWithFormatterAttributes::class, new SerializeConfig(), []));
    }

    public function testRetrieveMaxDepthFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int(), []),
        ], $loader->load(DummyWithMaxDepthAttribute::class, new SerializeConfig(), []));

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::bool(), [DummyWithMaxDepthAttribute::boolean(...)]),
        ], $loader->load(DummyWithMaxDepthAttribute::class, new SerializeConfig(), [
            'depth_counters' => [DummyWithMaxDepthAttribute::class => 256],
        ]));
    }
}
