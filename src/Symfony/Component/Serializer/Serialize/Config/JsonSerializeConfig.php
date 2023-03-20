<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Config;

use Symfony\Component\Serializer\Config\JsonConfig;

/**
 * JSON format serialization configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class JsonSerializeConfig extends JsonConfig
{
    public function __construct()
    {
        $this->flags = \JSON_PRESERVE_ZERO_FRACTION;
    }
}
