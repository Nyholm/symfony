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

/**
 * Loads properties serialization metadata for a given $className.
 *
 * This metadata can be used by the {@see Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface}
 * to create a more appropriate {@see Symfony\Component\Serializer\Serialize\DataModel\ObjectNode}.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param array<string, mixed> $context
     * @param class-string         $className
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, SerializeConfig $config, array $context): array;
}
