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
final readonly class YieldNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $value,
        public ?PhpNodeInterface $key = null,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        if (null === $this->key) {
            $compiler->raw(sprintf('yield %s', $compiler->subcompile($this->value)));

            return;
        }

        $compiler->raw(sprintf(
            'yield %s => %s',
            $compiler->subcompile($this->key),
            $compiler->subcompile($this->value),
        ));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->value),
            $optimizer->optimize($this->key),
        );
    }
}
