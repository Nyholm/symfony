<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Attribute;

/**
 * Defines the maximum serialization depth for the property.
 *
 * When the maximum depth is reached, the $maxDepthReachedFormatter callable is called if it has been defined.
 *
 * The first argument of that callable must be the input data.
 * Then, it is possible to inject the {@see Symfony\Component\Serializer\Serialize\Config\SerializeConfig}
 * and services thanks to their FQCN.
 *
 * It must return the new data.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class MaxDepth
{
    /**
     * @param positive-int $maxDepth
     * @param ?callable    $maxDepthReachedFormatter
     */
    public function __construct(
        public int $maxDepth,
        public mixed $maxDepthReachedFormatter = null,
    ) {
    }
}
