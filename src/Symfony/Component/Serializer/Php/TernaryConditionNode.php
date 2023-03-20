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
final readonly class TernaryConditionNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $condition,
        public PhpNodeInterface $onTrue,
        public PhpNodeInterface $onFalse,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('(')
            ->compile($this->condition)
            ->raw(' ? ')
            ->compile($this->onTrue)
            ->raw(' : ')
            ->compile($this->onFalse)
            ->raw(')');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->condition),
            $optimizer->optimize($this->onTrue),
            $optimizer->optimize($this->onFalse),
        );
    }
}
