<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Encoder;

use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;

/**
 * Encode $normalized data into a $resource stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface EncoderInterface
{
    /**
     * @param resource $resource
     */
    public static function encode(mixed $resource, mixed $normalized, SerializeConfig $config): void;
}
