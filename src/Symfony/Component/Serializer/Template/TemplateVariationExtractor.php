<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Template;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TemplateVariationExtractor implements TemplateVariationExtractorInterface
{
    public function extractVariationsFromType(Type $type): array
    {
        $groups = [];

        $findClassNames = static function (Type $type, array $classNames = []) use (&$findClassNames): array {
            if ($type->hasClass()) {
                $classNames[] = $type->className();

                return $classNames;
            }

            foreach ($type->genericParameterTypes() as $genericParameterType) {
                if (null !== $c = $findClassNames($genericParameterType, $classNames)) {
                    array_push($classNames, ...$c);
                }
            }

            foreach ($type->unionTypes() as $unionType) {
                if (null !== $c = $findClassNames($unionType, $classNames)) {
                    array_push($classNames, ...$c);
                }
            }

            foreach ($type->intersectionTypes() as $intersectionType) {
                if (null !== $c = $findClassNames($intersectionType, $classNames)) {
                    array_push($classNames, ...$c);
                }
            }

            return array_unique($classNames);
        };

        foreach ($findClassNames($type) as $className) {
            foreach ((new \ReflectionClass($className))->getProperties() as $reflectionProperty) {
                $reflectionAttribute = $reflectionProperty->getAttributes(Groups::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
                if (null === $reflectionAttribute) {
                    continue;
                }

                array_push($groups, ...$reflectionAttribute->newInstance()->groups);
            }
        }

        $groups = array_values(array_unique($groups));

        return array_map(fn (string $g): TemplateVariation => new GroupTemplateVariation($g), $groups);
    }

    public function extractVariationsFromConfig(SerializeConfig|DeserializeConfig $config): array
    {
        $variations = [];

        foreach ($config->groups() ?? [] as $group) {
            $variations[] = new GroupTemplateVariation($group);
        }

        return $variations;
    }
}
