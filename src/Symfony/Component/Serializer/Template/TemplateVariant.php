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

/**
 * Holds a serialization/deserialization configuration and the related
 * {@see Symfony\Component\Serializer\Template\TemplateVariation} combination.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class TemplateVariant
{
    /**
     * @var list<TemplateVariation>
     */
    public array $variations;

    public SerializeConfig|DeserializeConfig $config;

    /**
     * @param list<TemplateVariation> $variations
     */
    public function __construct(SerializeConfig|DeserializeConfig $config, array $variations)
    {
        usort($variations, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

        foreach ($variations as $variation) {
            $config = $variation->configure($config);
        }

        $this->variations = $variations;
        $this->config = $config;
    }
}
