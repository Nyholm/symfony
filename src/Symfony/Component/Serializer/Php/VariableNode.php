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
final readonly class VariableNode implements PhpNodeInterface
{
    public function __construct(
        public string $name,
        public bool $byReference = false,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf('%s$%s', $this->byReference ? '&' : '', $this->name));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
