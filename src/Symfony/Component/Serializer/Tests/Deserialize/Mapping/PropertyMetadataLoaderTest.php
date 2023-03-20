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
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;

class PropertyMetadataLoaderTest extends TestCase
{
    public function testExtractPropertyType()
    {
        $loader = new PropertyMetadataLoader(new PhpstanTypeExtractor(new ReflectionTypeExtractor()));

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int(), []),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(ClassicDummy::class, new DeserializeConfig(), []));
    }
}
