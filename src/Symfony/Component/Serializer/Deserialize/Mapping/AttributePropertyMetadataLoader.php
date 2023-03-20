<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;

use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * Enhance properties deserialization metadata based on properties' attributes.
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

    public function load(string $className, DeserializeConfig $config, array $context): array
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

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromParameter($reflectionFormatter->getParameters()[0]);

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
     * @return array{groups?: array<string, true>, name?: string, formatter?: callable}
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

        $reflectionAttribute = $reflectionProperty->getAttributes(DeserializeFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        return $metadata;
    }
}
