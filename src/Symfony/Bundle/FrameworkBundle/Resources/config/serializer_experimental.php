<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerLazyGhostCacheWarmer;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerTemplateCacheWarmer;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilder as DeserializeDataModelBuilder;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilderInterface as DeserializeDataModelBuilderInterface;
use Symfony\Component\Serializer\Deserialize\Decoder\CsvDecoder;
use Symfony\Component\Serializer\Deserialize\Decoder\JsonDecoder;
use Symfony\Component\Serializer\Deserialize\Deserializer;
use Symfony\Component\Serializer\Deserialize\DeserializerInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\EagerInstantiator;
use Symfony\Component\Serializer\Deserialize\Instantiator\LazyInstantiator;
use Symfony\Component\Serializer\Deserialize\Mapping\AttributePropertyMetadataLoader as DeserializeAttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader as DeserializePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface as DeserializePropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Deserialize\Mapping\TypePropertyMetadataLoader as DeserializeTypePropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonSplitter;
use Symfony\Component\Serializer\Deserialize\Template\EagerTemplateGenerator;
use Symfony\Component\Serializer\Deserialize\Template\LazyTemplateGenerator;
use Symfony\Component\Serializer\Deserialize\Template\Template as DeserializeTemplate;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilder as SerializeDataModelBuilder;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface as SerializeDataModelBuilderInterface;
use Symfony\Component\Serializer\Serialize\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serialize\Mapping\AttributePropertyMetadataLoader as SerializeAttributePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoader as SerializePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoaderInterface as SerializePropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Serialize\Mapping\TypePropertyMetadataLoader as SerializeTypePropertyMetadataLoader;
use Symfony\Component\Serializer\Serialize\Serializer;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Serialize\Template\JsonTemplateGenerator as SerializeJsonTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\NormalizerEncoderTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\Template as SerializeTemplate;
use Symfony\Component\Serializer\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.serializer.cache_dir.template', '%kernel.cache_dir%/serializer/template')
        ->set('.serializer.cache_dir.lazy_ghost', '%kernel.cache_dir%/serializer/lazy_ghost')
    ;

    $container->services()
        // Serializer/Deserializer
        ->set('serializer.serializer', Serializer::class)
            ->args([
                service('serializer.serialize.template'),
                abstract_arg('serialize/deserialize runtime services'),
                param('.serializer.cache_dir.template'),
            ])
        ->alias(SerializerInterface::class, 'serializer.serializer')

        ->set('serializer.deserializer', Deserializer::class)
            ->args([
                service('serializer.deserialize.template'),
                abstract_arg('serialize/deserialize runtime services'),
                service('serializer.instantiator'),
                param('.serializer.cache_dir.template'),
            ])
        ->alias(DeserializerInterface::class, 'serializer.deserializer')

        // Template
        ->set('serializer.serialize.template', SerializeTemplate::class)
            ->args([
                service('serializer.template_variation_extractor'),
                service('serializer.serialize.data_model_builder'),
                abstract_arg('serialize template generators'),
                param('.serializer.cache_dir.template'),
            ])

        ->set('serializer.deserialize.template', DeserializeTemplate::class)
            ->args([
                service('serializer.template_variation_extractor'),
                service('serializer.deserialize.data_model_builder'),
                abstract_arg('deserialize template generators'),
                param('.serializer.cache_dir.template'),
                param('serializer.lazy_deserialization'),
            ])

        // Template variations
        ->set('serializer.template_variation_extractor', TemplateVariationExtractor::class)
        ->alias(TemplateVariationExtractorInterface::class, 'serializer.template_variation_extractor')

        // Template generators
        ->set('serializer.serialize.template_generator.json', SerializeJsonTemplateGenerator::class)
            ->tag('serializer.serialize.template_generator', ['format' => 'json'])

        ->set('serializer.serialize.template_generator.csv', NormalizerEncoderTemplateGenerator::class)
            ->args([
                CsvEncoder::class,
            ])
            ->tag('serializer.serialize.template_generator', ['format' => 'csv'])

        ->set('serializer.deserialize.template_generator.json.eager', EagerTemplateGenerator::class)
            ->args([
                JsonDecoder::class,
            ])
            ->tag('serializer.deserialize.template_generator.eager', ['format' => 'json'])

        ->set('serializer.deserialize.template_generator.json.lazy', LazyTemplateGenerator::class)
            ->args([
                JsonDecoder::class,
                JsonSplitter::class,
            ])
            ->tag('serializer.deserialize.template_generator.lazy', ['format' => 'json'])

        ->set('serializer.deserialize.template_generator.csv.eager', EagerTemplateGenerator::class)
            ->args([
                CsvDecoder::class,
            ])
            ->tag('serializer.deserialize.template_generator.eager', ['format' => 'csv'])

        // Data model
        ->set('serializer.serialize.data_model_builder', SerializeDataModelBuilder::class)
            ->args([
                service('serializer.serialize.metadata.property_loader'),
                abstract_arg('serialize/deserialize runtime services'),
            ])
        ->alias(SerializeDataModelBuilderInterface::class, 'serializer.serialize.data_model_builder')

        ->set('serializer.deserialize.data_model_builder', DeserializeDataModelBuilder::class)
            ->args([
                service('serializer.deserialize.metadata.property_loader'),
                abstract_arg('serialize/deserialize runtime services'),
            ])
        ->alias(DeserializeDataModelBuilderInterface::class, 'serializer.deserialize.data_model_builder')

        // Instantiators
        ->set('serializer.instantiator.eager', EagerInstantiator::class)

        ->set('serializer.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.serializer.cache_dir.lazy_ghost'),
            ])

        // Metadata
        ->set('serializer.serialize.metadata.property_loader', SerializePropertyMetadataLoader::class)
            ->args([
                service('serializer.type_extractor'),
            ])

        ->set('serializer.serialize.metadata.property_loader.attribute', SerializeAttributePropertyMetadataLoader::class)
            ->decorate('serializer.serialize.metadata.property_loader')
            ->args([
                service('serializer.serialize.metadata.property_loader.attribute.inner'),
                service('serializer.type_extractor'),
            ])

        ->set('serializer.serialize.metadata.property_loader.type', SerializeTypePropertyMetadataLoader::class)
            ->decorate('serializer.serialize.metadata.property_loader')
            ->args([
                service('serializer.serialize.metadata.property_loader.type.inner'),
                service('serializer.type_extractor'),
            ])

        ->alias(SerializePropertyMetadataLoaderInterface::class, 'serializer.serialize.metadata.property_loader')

        ->set('serializer.deserialize.metadata.property_loader', DeserializePropertyMetadataLoader::class)
            ->args([
                service('serializer.type_extractor'),
            ])

        ->set('serializer.deserialize.metadata.property_loader.attribute', DeserializeAttributePropertyMetadataLoader::class)
            ->decorate('serializer.deserialize.metadata.property_loader')
            ->args([
                service('serializer.deserialize.metadata.property_loader.attribute.inner'),
                service('serializer.type_extractor'),
            ])

        ->set('serializer.deserialize.metadata.property_loader.type', DeserializeTypePropertyMetadataLoader::class)
            ->decorate('serializer.deserialize.metadata.property_loader')
            ->args([
                service('serializer.deserialize.metadata.property_loader.type.inner'),
                service('serializer.type_extractor'),
            ])

        ->alias(DeserializePropertyMetadataLoaderInterface::class, 'serializer.deserialize.metadata.property_loader')

        // Type extractors
        ->set('serializer.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->alias('serializer.type_extractor', 'serializer.type_extractor.reflection')
        ->alias(TypeExtractorInterface::class, 'serializer.type_extractor')

        // Cache
        ->set('serializer.cache_warmer.template', SerializerTemplateCacheWarmer::class)
            ->args([
                abstract_arg('serializable types'),
                service('serializer.serialize.template'),
                service('serializer.deserialize.template'),
                service('serializer.template_variation_extractor'),
                param('.serializer.cache_dir.template'),
                param('serializer.formats'),
                param('serializer.max_variants'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')

        ->set('serializer.cache_warmer.lazy_ghost', SerializerLazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('serializable types'),
                param('.serializer.cache_dir.lazy_ghost'),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
