<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Debug\TraceableEncoder;
use Symfony\Component\Serializer\Debug\TraceableNormalizer;
use Symfony\Component\Serializer\Serialize\SerializerInterface as ExperimentalSerializerInterface;

/**
 * Adds all services with the tags "serializer.encoder" and "serializer.normalizer" as
 * encoders and normalizers to the "serializer" service.
 *
 * @author Javier Lopez <f12loalf@gmail.com>
 * @author Robin Chalas <robin.chalas@gmail.com>
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer')) {
            return;
        }

        if (!$normalizers = $this->findAndSortTaggedServices('serializer.normalizer', $container)) {
            throw new RuntimeException('You must tag at least one service as "serializer.normalizer" to use the "serializer" service.');
        }

        if (!$encoders = $this->findAndSortTaggedServices('serializer.encoder', $container)) {
            throw new RuntimeException('You must tag at least one service as "serializer.encoder" to use the "serializer" service.');
        }

        if ($container->hasParameter('serializer.default_context')) {
            $defaultContext = $container->getParameter('serializer.default_context');
            foreach (array_merge($normalizers, $encoders) as $service) {
                $definition = $container->getDefinition($service);
                $definition->setBindings(['array $defaultContext' => new BoundArgument($defaultContext, false)] + $definition->getBindings());
            }

            $container->getParameterBag()->remove('serializer.default_context');
        }

        if ($container->getParameter('kernel.debug') && $container->hasDefinition('serializer.data_collector')) {
            foreach ($normalizers as $i => $normalizer) {
                $normalizers[$i] = $container->register('.debug.serializer.normalizer.'.$normalizer, TraceableNormalizer::class)
                    ->setArguments([$normalizer, new Reference('serializer.data_collector')]);
            }

            foreach ($encoders as $i => $encoder) {
                $encoders[$i] = $container->register('.debug.serializer.encoder.'.$encoder, TraceableEncoder::class)
                    ->setArguments([$encoder, new Reference('serializer.data_collector')]);
            }
        }

        $serializerDefinition = $container->getDefinition('serializer');
        $serializerDefinition->replaceArgument(0, $normalizers);
        $serializerDefinition->replaceArgument(1, $encoders);

        if (!interface_exists(ExperimentalSerializerInterface::class)) {
            return;
        }

        //
        // Experimental serializer
        //

        $serializeTemplateGenerators = [];
        foreach ($container->findTaggedServiceIds('serializer.serialize.template_generator') as $id => $tags) {
            $tag = reset($tags);
            $serializeTemplateGenerators[$tag['format']] = new Reference($id);
        }

        $container->getDefinition('serializer.serialize.template')
            ->replaceArgument(2, $serializeTemplateGenerators);

        $deserializeTemplateGenerators = [];
        foreach ($container->findTaggedServiceIds('serializer.deserialize.template_generator.eager') as $id => $tags) {
            $tag = reset($tags);
            $deserializeTemplateGenerators[$tag['format']]['eager'] = new Reference($id);
        }

        foreach ($container->findTaggedServiceIds('serializer.deserialize.template_generator.lazy') as $id => $tags) {
            $tag = reset($tags);
            $deserializeTemplateGenerators[$tag['format']]['lazy'] = new Reference($id);
        }

        $container->getDefinition('serializer.deserialize.template')
            ->replaceArgument(2, $deserializeTemplateGenerators);

        $serializable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('serializer.serializable')) {
                continue;
            }

            $serializable[] = $definition->getClass();
        }

        $container->getDefinition('serializer.cache_warmer.template')
            ->replaceArgument(0, $serializable);

        $container->getDefinition('serializer.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $serializable);
    }
}
