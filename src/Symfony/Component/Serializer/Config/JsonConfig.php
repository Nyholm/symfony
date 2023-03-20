<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Config;

/**
 * JSON format serialization/deserialization common configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract class JsonConfig
{
    protected int $flags = 0;

    /**
     * The flags bitmask.
     *
     * @see https://www.php.net/manual/en/json.constants.php
     *
     * @return positive-int
     */
    public function flags(): int
    {
        return $this->flags;
    }

    /**
     * @param positive-int $flags
     */
    public function withFlags(int $flags): static
    {
        $clone = clone $this;
        $clone->flags = $flags;

        return $clone;
    }
}
