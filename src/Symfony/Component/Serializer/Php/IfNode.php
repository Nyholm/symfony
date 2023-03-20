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
final readonly class IfNode implements PhpNodeInterface
{
    /**
     * @param list<PhpNodeInterface>                                                 $onIf
     * @param list<PhpNodeInterface>                                                 $onElse
     * @param list<array{condition: PhpNodeInterface, body: list<PhpNodeInterface>}> $elseIfs
     */
    public function __construct(
        public PhpNodeInterface $condition,
        public array $onIf,
        public array $onElse = [],
        public array $elseIfs = [],
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->line(sprintf('if (%s) {', $compiler->subcompile($this->condition)))
            ->indent();

        foreach ($this->onIf as $ifBodyNode) {
            $compiler->compile($ifBodyNode);
        }

        $compiler->outdent();

        foreach ($this->elseIfs as $elseIf) {
            $compiler
                ->line(sprintf('} elseif (%s) {', $compiler->subcompile($elseIf['condition'])))
                ->indent();

            foreach ($elseIf['body'] as $elseIfBodyNode) {
                $compiler->compile($elseIfBodyNode);
            }

            $compiler->outdent();
        }

        if ([] !== $this->onElse) {
            $compiler
                ->line('} else {')
                ->indent();

            foreach ($this->onElse as $elseBodyNode) {
                $compiler->compile($elseBodyNode);
            }

            $compiler->outdent();
        }

        $compiler->line('}');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->condition),
            $optimizer->optimize($this->onIf),
            $optimizer->optimize($this->onElse),
            array_map(fn (array $e): array => [
                'condition' => $optimizer->optimize($e['condition']),
                'body' => $optimizer->optimize($e['body']),
            ], $this->elseIfs),
        );
    }
}
