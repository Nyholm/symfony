<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\DataModel;

use Symfony\Component\Serializer\Type\Type;

/**
 * Represents a scalar in the data model graph representation.
 *
 * Scalars are the leaves of the data model tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class ScalarNode implements DataModelNodeInterface
{
    public function __construct(
        public Type $type,
    ) {
    }

    public function identifier(): string
    {
        return (string) $this->type;
    }

    public function type(): Type
    {
        return $this->type;
    }
}
