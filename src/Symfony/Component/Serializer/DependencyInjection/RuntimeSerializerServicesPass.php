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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Creates and injects a service locator containing serializable classes formatter's needed services.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class RuntimeSerializerServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer.serializer')) {
            return;
        }

        $formatters = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('serializer.serializable')) {
                continue;
            }

            array_push($formatters, ...$this->formatters($definition->getClass()));
        }

        $runtimeServices = [];
        foreach ($formatters as $formatter) {
            $formatterName = sprintf('%s::%s', $formatter->getClosureScopeClass()->getName(), $formatter->getName());
            foreach ($this->retrieveServices($container, $formatter) as $serviceName => $reference) {
                $runtimeServices[sprintf('%s[%s]', $formatterName, $serviceName)] = $reference;
            }
        }

        $runtimeServicesLocator = ServiceLocatorTagPass::register($container, $runtimeServices);

        $container->getDefinition('serializer.serializer')
            ->replaceArgument(1, $runtimeServicesLocator);

        $container->getDefinition('serializer.deserializer')
            ->replaceArgument(1, $runtimeServicesLocator);

        $container->getDefinition('serializer.serialize.data_model_builder')
            ->replaceArgument(1, $runtimeServicesLocator);

        $container->getDefinition('serializer.deserialize.data_model_builder')
            ->replaceArgument(1, $runtimeServicesLocator);
    }

    /**
     * @param class-string $className
     *
     * @return list<\ReflectionFunction>
     */
    private function formatters(string $className): array
    {
        $formatters = [];
        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (!\in_array($attribute->getName(), [SerializeFormatter::class, DeserializeFormatter::class, MaxDepth::class])) {
                    continue;
                }

                /** @var SerializeFormatter|DeserializeFormatter|MaxDepth $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $formatter = $attributeInstance instanceof SerializeFormatter || $attributeInstance instanceof DeserializeFormatter
                    ? $attributeInstance->formatter
                    : $attributeInstance->maxDepthReachedFormatter;

                $formatters[] = new \ReflectionFunction(\Closure::fromCallable($formatter));
            }
        }

        return $formatters;
    }

    /**
     * @return list<Reference>
     */
    private function retrieveServices(ContainerBuilder $container, \ReflectionFunction $function): array
    {
        $services = [];

        foreach ($function->getParameters() as $i => $parameter) {
            // first argument is always the data itself
            if (0 === $i) {
                continue;
            }

            $type = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));

            $invalidBehavior = match (true) {
                $parameter->isOptional() => ContainerInterface::IGNORE_ON_INVALID_REFERENCE,
                $parameter->allowsNull() => ContainerInterface::NULL_ON_INVALID_REFERENCE,
                default => ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE,
            };

            if ($autowireAttribute = ($parameter->getAttributes(Autowire::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)) {
                $value = $autowireAttribute->newInstance()->value;

                if ($value instanceof Reference) {
                    $services[$parameter->name] = $type
                        ? new TypedReference($value, $type, $invalidBehavior, $parameter->name)
                        : new Reference($value, $invalidBehavior);

                    continue;
                }

                $services[$parameter->name] = new Reference('.value.'.$container->hash($value));
                $container->register((string) $services[$parameter->name], 'mixed')
                    ->setFactory('current')
                    ->addArgument([$value]);

                continue;
            }

            if (is_a($type, SerializeConfig::class, allow_string: true) || is_a($type, DeserializeConfig::class, allow_string: true)) {
                continue;
            }

            if ('' === $type) {
                continue;
            }

            $services[$parameter->name] = new TypedReference($type, $type, $invalidBehavior, Target::parseName($parameter));
        }

        return $services;
    }
}
