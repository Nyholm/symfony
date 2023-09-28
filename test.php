<?php

use Symfony\Component\Serializer\Deserialize\Decoder\JsonDecoder;
use Symfony\Component\Serializer\Deserialize\Instantiator\EagerInstantiator;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonSplitter;
use Symfony\Component\Serializer\Deserialize\Template\EagerTemplateGenerator;
use Symfony\Component\Serializer\Deserialize\Template\LazyTemplateGenerator;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serialize\Template\JsonTemplateGenerator;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use Psr\Container\ContainerInterface;

require __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/Test/Resources/Cat.php';
require_once __DIR__.'/Test/Resources/Person.php';
require_once __DIR__.'/Test/Resources/DummyDenormalizer.php';

$person = new \Test\Resources\Person('John', 47);
$cat = new \Test\Resources\Cat('Jane', 11);
$person->setCat($cat);
$limit = 200000;
//$limit = 1;

function getSerializer(): \Symfony\Component\Serializer\Serialize\Serializer {
    $cacheDir = sprintf('%s/symfony_serializer_bench/template', sys_get_temp_dir());

    $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
    $runtimeServicesLocator = new class([]) implements ContainerInterface {
        use ServiceLocatorTrait;
    };


    $propertyMetadataLoader = new \Symfony\Component\Serializer\Serialize\Mapping\TypePropertyMetadataLoader(
        new \Symfony\Component\Serializer\Serialize\Mapping\AttributePropertyMetadataLoader(
            new \Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoader($typeExtractor),
            $typeExtractor
        ),
        $typeExtractor);

    $dataModelBuilder = new \Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilder(
        $propertyMetadataLoader,
        $runtimeServicesLocator,
    );
    $template = new Symfony\Component\Serializer\Serialize\Template\Template(new TemplateVariationExtractor(), $dataModelBuilder, ['json' => new JsonTemplateGenerator()], $cacheDir);
    return new \Symfony\Component\Serializer\Serialize\Serializer(
        $template,
        $runtimeServicesLocator,
        $cacheDir,
    );
}

function getDeserializer(): \Symfony\Component\Serializer\Deserialize\Deserializer {
    $templateCacheDir = sprintf('%s/symfony_serializer_bench/template', sys_get_temp_dir());

    $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
    $runtimeServicesLocator = new class([]) implements ContainerInterface {
        use ServiceLocatorTrait;
    };
    $instantiator = new EagerInstantiator();
    // $instantiator = new LazyInstantiator(sprintf('%s/symfony_serializer_bench/lazy_object', sys_get_temp_dir()));


    $propertyMetadataLoader = new \Symfony\Component\Serializer\Deserialize\Mapping\TypePropertyMetadataLoader(
        new \Symfony\Component\Serializer\Deserialize\Mapping\AttributePropertyMetadataLoader(
            new \Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader($typeExtractor),
            $typeExtractor
        ),
        $typeExtractor);


    $dataModelBuilder = new \Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilder($propertyMetadataLoader, $runtimeServicesLocator,);

    $template = new Symfony\Component\Serializer\Deserialize\Template\Template(
        new TemplateVariationExtractor(),
        $dataModelBuilder,
        ['json' => ['eager' => new EagerTemplateGenerator(JsonDecoder::class), 'lazy' => new LazyTemplateGenerator(JsonDecoder::class, JsonSplitter::class)]],
        $templateCacheDir,
        false,
    );

    return new \Symfony\Component\Serializer\Deserialize\Deserializer(
        $template,
        $runtimeServicesLocator,
        $instantiator,
        $templateCacheDir,
    );
}


$serializer = getSerializer();
$deserializer = getDeserializer();

// Warmup
$normalized = $serializer->serialize($person, 'json', new MemoryStream());

echo '## mtarld serializer'.\PHP_EOL;

$start = microtime(true);
for ($i = 0; $i < $limit; ++$i) {
    $normalized = $serializer->serialize($person, 'json');
}
$end = microtime(true);
echo 'Normalize: '.round(($end - $start) * 1000).' ms'.\PHP_EOL;


// Warmup
$deserializer->deserialize($normalized, Type::class(\Test\Resources\Person::class), 'json');

$start = microtime(true);
for ($i = 0; $i < $limit; ++$i) {
    $deserializer->deserialize($normalized, Type::class(\Test\Resources\Person::class), 'json');
}
$end = microtime(true);
echo 'Denormalize: '.round(($end - $start) * 1000).' ms'.\PHP_EOL;

