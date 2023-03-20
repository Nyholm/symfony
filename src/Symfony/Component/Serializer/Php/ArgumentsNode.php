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
final readonly class ArgumentsNode implements PhpNodeInterface
{
    /**
     * @param array<PhpNodeInterface> $arguments
     */
    public function __construct(
        public array $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(implode(', ', array_map(fn (PhpNodeInterface $n): string => $compiler->subcompile($n), $this->arguments)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->arguments));
    }
}
