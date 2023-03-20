<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\Serializer\DependencyInjection\RuntimeSerializerServicesPass;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

class RuntimeSerializerServicesPassTest extends TestCase
{
    public function testRetrieveServices()
    {
        $container = new ContainerBuilder();

        $container->register('serializer.serializer')->setArguments([null, null]);
        $container->register('serializer.deserializer')->setArguments([null, null]);
        $container->register('serializer.serialize.data_model_builder')->setArguments([null, null]);
        $container->register('serializer.deserialize.data_model_builder')->setArguments([null, null]);

        $container->register(ClassicDummy::class, ClassicDummy::class)->addTag('serializer.serializable');
        $container->register(DummyWithFormatterAttributes::class, DummyWithFormatterAttributes::class)->addTag('serializer.serializable');
        $container->register(DummyWithAttributesUsingServices::class, DummyWithAttributesUsingServices::class)->addTag('serializer.serializable');

        (new RuntimeSerializerServicesPass())->process($container);

        $runtimeServicesId = $container->getDefinition('serializer.serializer')->getArgument(1);

        $this->assertSame($runtimeServicesId, $container->getDefinition('serializer.deserializer')->getArgument(1));
        $this->assertSame($runtimeServicesId, $container->getDefinition('serializer.serialize.data_model_builder')->getArgument(1));
        $this->assertSame($runtimeServicesId, $container->getDefinition('serializer.deserialize.data_model_builder')->getArgument(1));

        $runtimeServices = $container->getDefinition($runtimeServicesId)->getArgument(0);

        $runtimeService = $runtimeServices[sprintf('%s::serviceAndSerializeConfig[typeExtractor]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            TypeExtractorInterface::class,
            TypeExtractorInterface::class,
            ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE,
            'typeExtractor',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::serviceAndSerializeConfig[serializer]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            SerializerInterface::class,
            SerializerInterface::class,
            ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE,
            'serializer',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::serviceAndSerializeConfig[serializer]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            SerializerInterface::class,
            SerializerInterface::class,
            ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE,
            'serializer',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::serviceAndDeserializeConfig[service]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            TypeExtractorInterface::class,
            TypeExtractorInterface::class,
            ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE,
            'service',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::autowireAttribute[service]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new Reference(
            'serializer.type_extractor',
            ContainerInterface::NULL_ON_INVALID_REFERENCE,
            'service',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::invalidNullableService[invalid]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            \InvalidInterface::class,
            \InvalidInterface::class,
            ContainerInterface::NULL_ON_INVALID_REFERENCE,
            'invalid',
        )], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::invalidOptionalService[invalid]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(
            \InvalidInterface::class,
            \InvalidInterface::class,
            ContainerInterface::IGNORE_ON_INVALID_REFERENCE,
            'invalid',
        )], $runtimeService->getValues());

        $this->assertArrayNotHasKey(sprintf('%s::skippedUnknownService[skipped]', DummyWithAttributesUsingServices::class), $runtimeServices);

        $this->assertArrayNotHasKey(sprintf('%s::serviceAndSerializeConfig[config]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::serviceAndDeserializeConfig[config]', DummyWithAttributesUsingServices::class), $runtimeServices);

        $this->assertArrayNotHasKey(sprintf('%s::serviceAndSerializeConfig[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::serviceAndDeserializeConfig[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::autowireAttribute[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::invalidNullableService[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::invalidOptionalService[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::skippedUnknownService[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
    }
}
