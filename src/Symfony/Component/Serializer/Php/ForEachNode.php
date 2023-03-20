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
final readonly class ForEachNode implements PhpNodeInterface
{
    /**
     * @param list<PhpNodeInterface> $body
     */
    public function __construct(
        public PhpNodeInterface $collection,
        public ?VariableNode $keyName,
        public VariableNode $valueName,
        public array $body,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        if (null === $this->keyName) {
            $compiler->line(sprintf(
                'foreach (%s as %s) {',
                $compiler->subcompile($this->collection),
                $compiler->subcompile($this->valueName),
            ));
        } else {
            $compiler->line(sprintf(
                'foreach (%s as %s => %s) {',
                $compiler->subcompile($this->collection),
                $compiler->subcompile($this->keyName),
                $compiler->subcompile($this->valueName),
            ));
        }

        $compiler->indent();

        foreach ($this->body as $bodyNode) {
            $compiler->compile($bodyNode);
        }

        $compiler
            ->outdent()
            ->line('}');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->collection),
            null !== $this->keyName ? $optimizer->optimize($this->keyName) : null,
            $optimizer->optimize($this->valueName),
            $optimizer->optimize($this->body),
        );
    }
}
