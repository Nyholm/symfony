<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Stream\StreamInterface;

/**
 * Serializes $data into a specific $format and $config to a string or into a given $output stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SerializerInterface
{
    public function serialize(mixed $data, string $format, StreamInterface $output = null, SerializeConfig $config = null): string|null;
}
