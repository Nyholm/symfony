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
final readonly class FunctionCallNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface|string $name,
        public ArgumentsNode $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $name = $this->name instanceof PhpNodeInterface ? sprintf('(%s)', $compiler->subcompile($this->name)) : $this->name;

        $compiler->raw(sprintf('%s(%s)', $name, $compiler->subcompile($this->arguments)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $this->name instanceof PhpNodeInterface ? $optimizer->optimize($this->name) : $this->name,
            $optimizer->optimize($this->arguments),
        );
    }
}
