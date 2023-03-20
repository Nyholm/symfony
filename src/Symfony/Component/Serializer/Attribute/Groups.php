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
 * Defines groups that will be used to filter the property according
 * to the groups given in the serialization/deserialization config.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Groups
{
    /**
     * @var list<non-empty-string>
     */
    public array $groups;

    /**
     * @param non-empty-string|non-empty-array<int, non-empty-string> $groups
     */
    public function __construct(string|array $groups)
    {
        $this->groups = array_values(array_unique((array) $groups));
    }
}
