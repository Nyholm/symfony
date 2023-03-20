<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilder;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface;
use Symfony\Component\Serializer\Serialize\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Serializer;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Serialize\Template\JsonTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\NormalizerEncoderTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\Template;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class SerializerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_serializer_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testSerializeWithResourceOutput()
    {
        $this->serializer()->serialize(true, 'json', $output = new MemoryStream());
        rewind($output->resource());

        $this->assertSame('true', stream_get_contents($output->resource()));
    }

    public function testSerializeScalar()
    {
        $this->assertSame('null', $this->serializer()->serialize(null, 'json'));
        $this->assertSame('true', $this->serializer()->serialize(true, 'json'));
        $this->assertSame(
            '[{"foo":1,"bar":2},{"foo":3}]',
            $this->serializer()->serialize([['foo' => 1, 'bar' => 2], ['foo' => 3]], 'json'),
        );

        return;
        $this->assertEquals(
            '{"foo":"bar"}',
            $this->serializer()->serialize((object) ['foo' => 'bar'], 'json'),
        );
        $this->assertEquals(
            'ONE',
            $this->serializer()->serialize(DummyBackedEnum::ONE, 'json'),
        );
    }

    public function testOverrideType()
    {
        $this->assertSame('{"foo":"bar"}', $this->serializer()->serialize(['foo' => 'bar'], 'json'));
        $this->assertSame('["bar"]', $this->serializer()->serialize(['foo' => 'bar'], 'json', config: (new SerializeConfig())->withType(Type::list(Type::string()))));
    }

    public function testSerializeObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals('{"id":10,"name":"dummy name"}', $this->serializer()->serialize($dummy, 'json'));
    }

    public function testSerializeObjectWithSerializedName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            '{"@id":10,"name":"dummy name"}',
            $this->serializer()->serialize($dummy, 'json'),
        );
    }

    public function testSerializeObjectWithSerializeFormatter()
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            '{"id":"20","name":"dummy name"}',
            $this->serializer()->serialize($dummy, 'json'),
        );
    }

    public function testSerializeObjectWithGroupsAttribute()
    {
        $dummyWithoutGroup = new DummyWithGroups();
        $dummyWithoutGroup->none = 'set';
        $dummyWithoutGroup->one = 'set';
        $dummyWithoutGroup->oneAndTwo = 'set';
        $dummyWithoutGroup->twoAndThree = 'set';

        $this->assertEquals(
            '{"none":"set","one":"set","oneAndTwo":"set","twoAndThree":"set"}',
            $this->serializer()->serialize($dummyWithoutGroup, 'json'),
        );

        $dummyWithGroupOne = new DummyWithGroups();
        $dummyWithGroupOne->one = 'set';
        $dummyWithGroupOne->oneAndTwo = 'set';

        $this->assertEquals(
            '{"one":"set","oneAndTwo":"set"}',
            $this->serializer()->serialize($dummyWithGroupOne, 'json', config: (new SerializeConfig())->withGroups('one')),
        );

        $dummyWithGroupTwo = new DummyWithGroups();
        $dummyWithGroupTwo->oneAndTwo = 'set';
        $dummyWithGroupTwo->twoAndThree = 'set';

        $this->assertEquals(
            '{"oneAndTwo":"set","twoAndThree":"set"}',
            $this->serializer()->serialize($dummyWithGroupTwo, 'json', config: (new SerializeConfig())->withGroups('two')),
        );

        $this->assertEquals(
            '{}',
            $this->serializer()->serialize(new DummyWithGroups(), 'json', config: (new SerializeConfig())->withGroups('other')),
        );
    }

    public function testCreateCacheFile()
    {
        $this->serializer()->serialize(true, 'json');

        $this->assertFileExists($this->cacheDir);
        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $template = new Template(
            new TemplateVariationExtractor(),
            $this->createStub(DataModelBuilderInterface::class),
            [],
            $this->cacheDir,
            false,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $cacheFilename = $template->path(Type::bool(), 'json', new SerializeConfig());
        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };');

        $this->assertSame('CACHED', $this->serializer()->serialize(true, 'json'));
    }

    public function testRecreateCacheFileIfForceGenerateTemplate()
    {
        $template = new Template(
            new TemplateVariationExtractor(),
            $this->createStub(DataModelBuilderInterface::class),
            [],
            $this->cacheDir,
            false,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $cacheFilename = $template->path(Type::bool(), 'json', new SerializeConfig());
        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };');

        $this->assertSame('true', $this->serializer()->serialize(true, 'json', config: (new SerializeConfig())->withForceGenerateTemplate()));
    }

    /**
     * @param array<string, mixed> $runtimeServices
     */
    private function serializer(array $runtimeServices = []): SerializerInterface
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $propertyMetadataLoader = new TypePropertyMetadataLoader(
            new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor),
            $typeExtractor,
        );
        $runtimeServicesLocator = new class($runtimeServices) implements ContainerInterface {
            use ServiceLocatorTrait;
        };
        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServicesLocator);
        $template = new Template(
            new TemplateVariationExtractor(),
            $dataModeBuilder,
            [
                'json' => new JsonTemplateGenerator(),
                'csv' => new NormalizerEncoderTemplateGenerator(CsvEncoder::class),
            ],
            $this->cacheDir,
        );

        return new Serializer($template, $runtimeServicesLocator, $this->cacheDir);
    }
}
