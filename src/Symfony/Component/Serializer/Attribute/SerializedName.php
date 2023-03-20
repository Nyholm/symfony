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
 * Defines the serialized property name.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class SerializedName
{
    public function __construct(
        public string $name,
    ) {
    }
}
