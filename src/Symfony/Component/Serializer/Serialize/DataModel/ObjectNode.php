<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\DataModel;

use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * Represents an object in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param array<string, DataModelNodeInterface> $properties
     */
    public function __construct(
        public PhpNodeInterface $accessor,
        public Type $type,
        public array $properties,
    ) {
    }

    public function type(): Type
    {
        return $this->type;
    }
}
