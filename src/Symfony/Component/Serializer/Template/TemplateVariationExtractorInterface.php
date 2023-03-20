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

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\Type;

/**
 * Extracts {@see Symfony\Component\Serializer\Template\TemplateVariation} from
 * a given type or serialization/deserialization configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface TemplateVariationExtractorInterface
{
    /**
     * @return list<TemplateVariation>
     */
    public function extractVariationsFromType(Type $type): array;

    /**
     * @return list<TemplateVariation>
     */
    public function extractVariationsFromConfig(SerializeConfig|DeserializeConfig $config): array;
}
