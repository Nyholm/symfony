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
final readonly class ClosureNode implements PhpNodeInterface
{
    /**
     * @param list<PhpNodeInterface> $body
     */
    public function __construct(
        public ParametersNode $parameters,
        public ?string $returnType,
        public bool $static,
        public array $body,
        public ?ArgumentsNode $uses = null,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $staticSource = $this->static ? 'static ' : '';
        $parametersSource = $compiler->subcompile($this->parameters);
        $usesSource = null !== $this->uses ? sprintf(' use (%s)', $compiler->subcompile($this->uses)) : '';
        $returnTypeSource = $this->returnType ? ': '.$this->returnType : '';

        $compiler
            ->raw(sprintf('%sfunction (%s)%s%s {', $staticSource, $parametersSource, $usesSource, $returnTypeSource)."\n")
            ->indent();

        foreach ($this->body as $bodyNode) {
            $compiler->compile($bodyNode);
        }

        $compiler
            ->outdent()
            ->raw('}', indent: true);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->parameters),
            $this->returnType,
            $this->static,
            $optimizer->optimize($this->body),
            null !== $this->uses ? $optimizer->optimize($this->uses) : null,
        );
    }
}
