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
 * Generic template variation representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract readonly class TemplateVariation implements \Stringable
{
    public function __construct(
        public string $type,
        public string $value,
    ) {
    }

    /**
     * @template T of SerializeConfig|DeserializeConfig
     *
     * @param T $config
     *
     * @return T
     */
    abstract public function configure(SerializeConfig|DeserializeConfig $config): SerializeConfig|DeserializeConfig;

    public function compare(self $other): int
    {
        return (string) $this <=> (string) $other;
    }

    public function __toString(): string
    {
        return sprintf('%s-%s', $this->type, $this->value);
    }
}
