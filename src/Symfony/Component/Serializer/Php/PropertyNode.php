<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Php;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class PropertyNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $object,
        public string $property,
        public bool $static = false,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->object)
            ->raw(sprintf('%s%s', $this->static ? '::$' : '->', $this->property));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->object),
            $this->property,
            $this->static,
        );
    }
}
