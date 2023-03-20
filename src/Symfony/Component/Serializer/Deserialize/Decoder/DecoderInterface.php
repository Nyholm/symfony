<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Decoder;

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * Decodes a subset of a given $resource stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface DecoderInterface
{
    /**
     * @param resource $resource
     *
     * @throws InvalidResourceException
     */
    public static function decode(mixed $resource, int $offset, int $length, DeserializeConfig $config): mixed;
}
