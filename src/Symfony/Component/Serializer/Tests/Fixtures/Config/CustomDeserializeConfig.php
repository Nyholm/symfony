<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Fixtures\Config;

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;

final class CustomDeserializeConfig extends DeserializeConfig
{
    protected int $scale = 2;

    public function scale(): int
    {
        return $this->scale;
    }

    public function withScale(int $scale): static
    {
        $clone = clone $this;
        $clone->scale = $scale;

        return $clone;
    }
}
