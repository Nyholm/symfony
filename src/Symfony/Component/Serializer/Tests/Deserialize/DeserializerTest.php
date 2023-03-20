<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilder;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilderInterface;
use Symfony\Component\Serializer\Deserialize\Decoder\CsvDecoder;
use Symfony\Component\Serializer\Deserialize\Decoder\JsonDecoder;
use Symfony\Component\Serializer\Deserialize\Deserializer;
use Symfony\Component\Serializer\Deserialize\DeserializerInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\EagerInstantiator;
use Symfony\Component\Serializer\Deserialize\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonSplitter;
use Symfony\Component\Serializer\Deserialize\Template\EagerTemplateGenerator;
use Symfony\Component\Serializer\Deserialize\Template\LazyTemplateGenerator;
use Symfony\Component\Serializer\Deserialize\Template\Template;
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

class DeserializerTest extends TestCase
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

    public function testDeserializeWithStringInput()
    {
        $this->assertTrue($this->deserializer()->deserialize('true', Type::bool(), 'json'));
    }

    public function testDeserializeScalar()
    {
        $this->assertNull($this->deserializer()->deserialize(new MemoryStream('null'), Type::int(nullable: true), 'json'));
        $this->assertTrue($this->deserializer()->deserialize(new MemoryStream('true'), Type::bool(), 'json'));
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->deserializer()->deserialize(new MemoryStream('[{"foo": 1, "bar": 2}, {"foo": 3}]'), Type::array(), 'json'),
        );
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->deserializer()->deserialize(new MemoryStream('[{"foo": 1, "bar": 2}, {"foo": 3}]'), Type::iterable(), 'json'),
        );
        $this->assertEquals(
            (object) ['foo' => 'bar'],
            $this->deserializer()->deserialize(new MemoryStream('{"foo": "bar"}'), Type::object(), 'json'),
        );
        $this->assertEquals(
            DummyBackedEnum::ONE,
            $this->deserializer()->deserialize(new MemoryStream('1'), Type::enum(DummyBackedEnum::class), 'json'),
        );
    }

    public function testDeserializeCollection()
    {
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->deserializer()->deserialize(new MemoryStream('[{"foo": 1, "bar": 2}, {"foo": 3}]'), Type::list(Type::dict(Type::int())), 'json'),
        );

        $iterable = $this->deserializer()->deserialize(new MemoryStream('[{"foo": 1, "bar": 2}, {"foo": 3}]'), Type::iterableList(Type::iterableDict(Type::int())), 'json');
        $this->assertIsIterable($iterable);
        $array = [];
        foreach ($iterable as $item) {
            $array[] = iterator_to_array($item);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['foo' => 3]], $array);
    }

    public function testDeserializeObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            $dummy,
            $this->deserializer()->deserialize(new MemoryStream('{"id": 10, "name": "dummy name"}'), Type::class(ClassicDummy::class), 'json'),
        );
    }

    public function testDeserializeObjectWithSerializedName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;

        $this->assertEquals(
            $dummy,
            $this->deserializer()->deserialize(new MemoryStream('{"@id": 10}'), Type::class(DummyWithNameAttributes::class), 'json'),
        );
    }

    public function testDeserializeObjectWithDeserializeFormatter()
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;

        $this->assertEquals(
            $dummy,
            $this->deserializer()->deserialize(new MemoryStream('{"id": "20"}'), Type::class(DummyWithFormatterAttributes::class), 'json'),
        );
    }

    public function testDeserializeObjectWithGroupsAttribute()
    {
        $dummyWithoutGroup = new DummyWithGroups();
        $dummyWithoutGroup->none = 'set';
        $dummyWithoutGroup->one = 'set';
        $dummyWithoutGroup->oneAndTwo = 'set';
        $dummyWithoutGroup->twoAndThree = 'set';

        $this->assertEquals(
            $dummyWithoutGroup,
            $this->deserializer()->deserialize(
                new MemoryStream('{"none": "set", "one": "set", "oneAndTwo": "set", "twoAndThree": "set"}'),
                Type::class(DummyWithGroups::class),
                'json',
            ),
        );

        $dummyWithGroupOne = new DummyWithGroups();
        $dummyWithGroupOne->one = 'set';
        $dummyWithGroupOne->oneAndTwo = 'set';

        $this->assertEquals(
            $dummyWithGroupOne,
            $this->deserializer()->deserialize(
                new MemoryStream('{"none": "set", "one": "set", "oneAndTwo": "set", "twoAndThree": "set"}'),
                Type::class(DummyWithGroups::class),
                'json',
                (new DeserializeConfig())->withGroups('one'),
            ),
        );

        $dummyWithGroupTwo = new DummyWithGroups();
        $dummyWithGroupTwo->oneAndTwo = 'set';
        $dummyWithGroupTwo->twoAndThree = 'set';

        $this->assertEquals(
            $dummyWithGroupTwo,
            $this->deserializer()->deserialize(
                new MemoryStream('{"none": "set", "one": "set", "oneAndTwo": "set", "twoAndThree": "set"}'),
                Type::class(DummyWithGroups::class),
                'json',
                (new DeserializeConfig())->withGroups('two'),
            ),
        );

        $this->assertEquals(
            new DummyWithGroups(),
            $this->deserializer()->deserialize(
                new MemoryStream('{"none": "set", "one": "set", "oneAndTwo": "set", "twoAndThree": "set"}'),
                Type::class(DummyWithGroups::class),
                'json',
                (new DeserializeConfig())->withGroups('other'),
            ),
        );
    }

    public function testCreateCacheFile()
    {
        $this->deserializer()->deserialize(new MemoryStream('true'), Type::bool(), 'json');

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

        $cacheFilename = $template->path(Type::bool(), 'json', new DeserializeConfig());
        file_put_contents($cacheFilename, '<?php return static function () { return "CACHED"; };');

        $this->assertSame('CACHED', $this->deserializer()->deserialize(new MemoryStream('true'), Type::bool(), 'json'));
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

        $cacheFilename = $template->path(Type::bool(), 'json', new DeserializeConfig());
        file_put_contents($cacheFilename, '<?php return static function () { return "CACHED"; };');

        $this->assertTrue($this->deserializer()->deserialize(
            new MemoryStream('true'),
            Type::bool(),
            'json',
            (new DeserializeConfig())->withForceGenerateTemplate(),
        ));
    }

    /**
     * @param array<string, mixed> $runtimeServices
     */
    private function deserializer(array $runtimeServices = []): DeserializerInterface
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
                'json' => [
                    'eager' => new EagerTemplateGenerator(JsonDecoder::class),
                    'lazy' => new LazyTemplateGenerator(JsonDecoder::class, JsonSplitter::class),
                ],
                'csv' => [
                    'eager' => new EagerTemplateGenerator(CsvDecoder::class),
                ],
            ],
            $this->cacheDir,
            false,
        );
        $instantiator = new EagerInstantiator();

        return new Deserializer($template, $runtimeServicesLocator, $instantiator, $this->cacheDir);
    }
}
