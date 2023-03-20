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
final readonly class ArrayAccessNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $array,
        public ?PhpNodeInterface $key,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf(
            '%s[%s]',
            $compiler->subcompile($this->array),
            null !== $this->key ? $compiler->subcompile($this->key) : '',
        ));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->array),
            null !== $this->key ? $optimizer->optimize($this->key) : null,
        );
    }
}
