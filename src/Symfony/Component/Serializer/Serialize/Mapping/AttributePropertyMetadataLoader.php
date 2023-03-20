<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Mapping;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * Enhance properties serialization metadata based on properties' attributes.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class AttributePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $decorated,
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function load(string $className, SerializeConfig $config, array $context): array
    {
        $initialResult = $this->decorated->load($className, $config, $context);
        $result = [];

        foreach ($initialResult as $initialSerializedName => $initialMetadata) {
            $attributesMetadata = $this->propertyAttributesMetadata(new \ReflectionProperty($className, $initialMetadata->name()));

            if ([] !== $config->groups()) {
                $matchingGroup = false;
                foreach ($config->groups() as $group) {
                    if (isset($attributesMetadata['groups'][$group])) {
                        $matchingGroup = true;

                        break;
                    }
                }

                if (!$matchingGroup) {
                    continue;
                }
            }

            $serializedName = $attributesMetadata['name'] ?? $initialSerializedName;

            if (isset($attributesMetadata['max_depth']) && ($context['depth_counters'][$className] ?? 0) > $attributesMetadata['max_depth']) {
                if (null === $formatter = $attributesMetadata['max_depth_reached_formatter'] ?? null) {
                    continue;
                }

                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromFunctionReturn($reflectionFormatter);

                $result[$serializedName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromFunctionReturn($reflectionFormatter);

                $result[$serializedName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            $result[$serializedName] = $initialMetadata;
        }

        return $result;
    }

    /**
     * @return array{groups?: array<string, true>, name?: string, formatter?: callable, max_depth?: int, max_depth_reached_formatter?: ?callable}
     */
    private function propertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(Groups::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            foreach ($reflectionAttribute->newInstance()->groups as $group) {
                $metadata['groups'][$group] = true;
            }
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(SerializedName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(SerializeFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(MaxDepth::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $attributeInstance = $reflectionAttribute->newInstance();

            $metadata['max_depth'] = $attributeInstance->maxDepth;
            $metadata['max_depth_reached_formatter'] = $attributeInstance->maxDepthReachedFormatter;
        }

        return $metadata;
    }
}
