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
 * Defines a callable that will be used to format the property data during deserialization.
 *
 * The first argument of that callable must be the input data.
 * Then, it is possible to inject the {@see Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig}
 * and services thanks to their FQCN.
 *
 * It must return the formatted data.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class DeserializeFormatter
{
    /**
     * @param callable $formatter
     */
    public function __construct(
        public mixed $formatter,
    ) {
    }
}
