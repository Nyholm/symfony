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
use Symfony\Component\Serializer\Deserialize\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
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
            array_keys($loader->load(DummyWithGroups::class, new DeserializeConfig(), [])),
        );

        $this->assertSame(
            ['one', 'oneAndTwo'],
            array_keys($loader->load(DummyWithGroups::class, (new DeserializeConfig())->withGroups(['one']), [])),
        );

        $this->assertSame(
            ['oneAndTwo', 'twoAndThree'],
            array_keys($loader->load(DummyWithGroups::class, (new DeserializeConfig())->withGroups(['two']), [])),
        );

        $this->assertSame(
            ['twoAndThree'],
            array_keys($loader->load(DummyWithGroups::class, (new DeserializeConfig())->withGroups(['three']), [])),
        );

        $this->assertSame([], $loader->load(DummyWithGroups::class, (new DeserializeConfig())->withGroups(['other']), []));
    }

    public function testRetrieveSerializedName()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertSame(['@id', 'name'], array_keys($loader->load(DummyWithNameAttributes::class, new DeserializeConfig(), [])));
    }

    public function testRetrieveDeserializeFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::string(), [DummyWithFormatterAttributes::divideAndCastToInt(...)]),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(DummyWithFormatterAttributes::class, new DeserializeConfig(), []));
    }
}
