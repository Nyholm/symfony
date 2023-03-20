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
 * Represents a collection in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class CollectionNode implements DataModelNodeInterface
{
    public function __construct(
        public Type $type,
        public DataModelNodeInterface $item,
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
