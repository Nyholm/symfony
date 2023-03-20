<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Template\GroupTemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Type\Type;

class TemplateVariationExtractorTest extends TestCase
{
    public function testExtractFromType()
    {
        $extractor = new TemplateVariationExtractor();

        $this->assertEquals([], $extractor->extractVariationsFromType(Type::int()));
        $this->assertEquals([], $extractor->extractVariationsFromType(Type::class(ClassicDummy::class)));

        $this->assertEquals([
            new GroupTemplateVariation('one'),
            new GroupTemplateVariation('two'),
            new GroupTemplateVariation('three'),
        ], $extractor->extractVariationsFromType(Type::class(DummyWithGroups::class)));

        $this->assertEquals([
            new GroupTemplateVariation('one'),
            new GroupTemplateVariation('two'),
            new GroupTemplateVariation('three'),
        ], $extractor->extractVariationsFromType(Type::list(Type::class(DummyWithGroups::class))));

        $this->assertEquals([
            new GroupTemplateVariation('one'),
            new GroupTemplateVariation('two'),
            new GroupTemplateVariation('three'),
        ], $extractor->extractVariationsFromType(Type::union(Type::int(), Type::class(DummyWithGroups::class))));

        $this->assertEquals([
            new GroupTemplateVariation('one'),
            new GroupTemplateVariation('two'),
            new GroupTemplateVariation('three'),
        ], $extractor->extractVariationsFromType(Type::intersection(Type::int(), Type::class(DummyWithGroups::class))));
    }

    public function testExtractFromConfig()
    {
        $extractor = new TemplateVariationExtractor();

        $serializeConfig = (new SerializeConfig())->withGroups(['a', 'b', 'c']);
        $deserializeConfig = (new DeserializeConfig())->withGroups(['a', 'b', 'c']);

        $this->assertEquals([
            new GroupTemplateVariation('a'),
            new GroupTemplateVariation('b'),
            new GroupTemplateVariation('c'),
        ], $extractor->extractVariationsFromConfig($serializeConfig));

        $this->assertEquals([
            new GroupTemplateVariation('a'),
            new GroupTemplateVariation('b'),
            new GroupTemplateVariation('c'),
        ], $extractor->extractVariationsFromConfig($deserializeConfig));
    }
}
