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

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;

/**
 * Loads properties deserialization metadata for a given $className.
 *
 * This metadata can be used by the {@see Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilderInterface}
 * to create a more appropriate {@see Symfony\Component\Serializer\Deserialize\DataModel\ObjectNode}.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, DeserializeConfig $config, array $context): array;
}
