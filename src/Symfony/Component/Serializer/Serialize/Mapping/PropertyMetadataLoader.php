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

use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * Loads basic properties serialization metadata for a given $className.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function load(string $className, SerializeConfig $config, array $context): array
    {
        $result = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPublic()) {
                continue;
            }

            $name = $serializedName = $reflectionProperty->getName();
            $type = $this->typeExtractor->extractTypeFromProperty($reflectionProperty);

            $result[$serializedName] = new PropertyMetadata($name, $type);
        }

        return $result;
    }
}
