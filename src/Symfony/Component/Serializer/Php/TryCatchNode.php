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
final readonly class TryCatchNode implements PhpNodeInterface
{
    /**
     * @param list<PhpNodeInterface> $tryNodes
     * @param list<PhpNodeInterface> $catchNodes
     */
    public function __construct(
        public array $tryNodes,
        public array $catchNodes,
        public ParametersNode $catchParameters,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->line('try {')
            ->indent();

        foreach ($this->tryNodes as $node) {
            $compiler->compile($node);
        }

        $compiler
            ->outdent()
            ->line(sprintf('} catch (%s) {', $compiler->subcompile($this->catchParameters)))
            ->indent();

        foreach ($this->catchNodes as $node) {
            $compiler->compile($node);
        }

        $compiler
            ->outdent()
            ->line('}');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->tryNodes),
            $optimizer->optimize($this->catchNodes),
            $optimizer->optimize($this->catchParameters),
        );
    }
}
