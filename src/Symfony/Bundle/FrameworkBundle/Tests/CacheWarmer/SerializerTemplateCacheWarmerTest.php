<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerTemplateCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilderInterface as DeserializeDataModelBuilderInterface;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelNodeInterface as DeserializeDataModelNodeInterface;
use Symfony\Component\Serializer\Deserialize\Template\Template as DeserializeTemplate;
use Symfony\Component\Serializer\Deserialize\Template\TemplateGeneratorInterface as DeserializeTemplateGeneratorInterface;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface as SerializeDataModelBuilderInterface;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelNodeInterface as SerializeDataModelNodeInterface;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Serialize\Template\Template as SerializeTemplate;
use Symfony\Component\Serializer\Serialize\Template\TemplateGeneratorInterface as SerializeTemplateGeneratorInterface;
use Symfony\Component\Serializer\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Type\Type;

class SerializerTemplateCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!interface_exists(SerializerInterface::class)) {
            $this->markTestSkipped('experimental version of symfony/serializer is required');
        }

        $this->cacheDir = sprintf('%s/symfony_serializer_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUpTemplates()
    {
        $this->cacheWarmer([ClassicDummy::class], ['foo'])->warmUp('useless');

        $this->assertSame([
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.serialize.foo.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    public function testWarmUpTemplateWithMultipleFormats()
    {
        $this->cacheWarmer([ClassicDummy::class], ['foo', 'bar'])->warmUp('useless');

        $this->assertSame([
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.deserialize.eager.bar.php', $this->cacheDir),
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.serialize.bar.php', $this->cacheDir),
            sprintf('%s/c13f5526678495e20da82e0a7c1c300b.serialize.foo.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    public function testWarmUpTemplateWithGroupsVariants()
    {
        $this->cacheWarmer([DummyWithGroups::class], ['foo'], 32)->warmUp('useless');

        $this->assertSame([
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.42bacc6e7c63de24830ff243836b6ce5.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.42bacc6e7c63de24830ff243836b6ce5.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.613dc1622a961367df54936cc0b16b1c.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.613dc1622a961367df54936cc0b16b1c.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.d43c98b6d28073d6496d6801c2a33116.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.d43c98b6d28073d6496d6801c2a33116.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec0c1cfa27836dcd1e4380f64457b33c.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec0c1cfa27836dcd1e4380f64457b33c.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec63e403ed2a277f78a6fb538c960e45.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec63e403ed2a277f78a6fb538c960e45.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.eed85c52626ddd6155fcc5a720f6e73a.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.eed85c52626ddd6155fcc5a720f6e73a.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.f6aed1561b42f63fed1657d6430523e2.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.f6aed1561b42f63fed1657d6430523e2.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.serialize.foo.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    public function testWarmUpTemplateSliceWhenTooManyVariants()
    {
        $this->cacheWarmer([DummyWithGroups::class], ['foo'], 3)->warmUp('useless');

        $this->assertSame([
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec63e403ed2a277f78a6fb538c960e45.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.ec63e403ed2a277f78a6fb538c960e45.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.f6aed1561b42f63fed1657d6430523e2.deserialize.eager.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.f6aed1561b42f63fed1657d6430523e2.serialize.foo.php', $this->cacheDir),
            sprintf('%s/16eccb6414dc03933f4799de52d9f6a8.serialize.foo.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    /**
     * @param list<class-string> $serializable
     * @param list<string>       $formats
     */
    private function cacheWarmer(array $serializable, array $formats, int $maxVariants = 32): SerializerTemplateCacheWarmer
    {
        $templateVariationExtractor = new TemplateVariationExtractor();

        $serializeTemplateGenerator = $this->createStub(SerializeTemplateGeneratorInterface::class);
        $deserializeTemplateGenerator = $this->createStub(DeserializeTemplateGeneratorInterface::class);

        $serializeDataModelNode = $this->createStub(SerializeDataModelNodeInterface::class);
        $serializeDataModelNode->method('type')->willReturn(Type::int());

        $deserializeDataModelNode = $this->createStub(DeserializeDataModelNodeInterface::class);
        $deserializeDataModelNode->method('type')->willReturn(Type::int());

        $serializeDataModelBuilder = $this->createStub(SerializeDataModelBuilderInterface::class);
        $serializeDataModelBuilder->method('build')->willReturn($serializeDataModelNode);

        $deserializeDataModelBuilder = $this->createStub(DeserializeDataModelBuilderInterface::class);
        $deserializeDataModelBuilder->method('build')->willReturn($deserializeDataModelNode);

        return new SerializerTemplateCacheWarmer(
            $serializable,
            new SerializeTemplate(
                $templateVariationExtractor,
                $serializeDataModelBuilder,
                ['foo' => $serializeTemplateGenerator, 'bar' => $serializeTemplateGenerator, ],
                $this->cacheDir,
            ),
            new DeserializeTemplate(
                $templateVariationExtractor,
                $deserializeDataModelBuilder,
                [
                    'foo' => ['eager' => $deserializeTemplateGenerator, 'lazy' => $deserializeTemplateGenerator, ],
                    'bar' => ['eager' => $deserializeTemplateGenerator, ],
                ],
                $this->cacheDir,
                false,
            ),
            $templateVariationExtractor,
            $this->cacheDir,
            $formats,
            $maxVariants,
            new NullLogger(),
        );
    }
}
