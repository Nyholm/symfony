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
final readonly class NewNode implements PhpNodeInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public string $class,
        public ArgumentsNode $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf('new %s(%s)', $this->class, $compiler->subcompile($this->arguments)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $this->class,
            $optimizer->optimize($this->arguments),
        );
    }
}
