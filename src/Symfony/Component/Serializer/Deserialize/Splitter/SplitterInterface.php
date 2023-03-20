<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Splitter;

/**
 * Splits a list or a dictionnary in a subset of a given $resource stream and yields tokens.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SplitterInterface
{
    /**
     * @param resource $resource
     *
     * @return \Iterator<int, array{0: int, 1: int}>|null
     */
    public static function splitList(mixed $resource, int $offset = 0, int $length = -1): ?\Iterator;

    /**
     * @param resource $resource
     *
     * @return \Iterator<string, array{0: int, 1: int}>|null
     */
    public static function splitDict(mixed $resource, int $offset = 0, int $length = -1): ?\Iterator;
}
