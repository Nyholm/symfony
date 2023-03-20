<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Instantiator;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Instantiates a new $className object with the given $properties values.
 *
 * A property must be a callable that return the property value when being
 * called to permit laziness when needed.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface InstantiatorInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T>                  $className
     * @param array<string, callable(): mixed> $properties
     *
     * @return T
     *
     * @throws UnexpectedValueException
     */
    public function instantiate(string $className, array $properties): object;
}
