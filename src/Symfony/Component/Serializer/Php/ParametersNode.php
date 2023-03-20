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
final readonly class ParametersNode implements PhpNodeInterface
{
    /**
     * @param array<string, ?string> $parameters
     */
    public function __construct(
        public array $parameters,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $argumentSources = [];

        foreach ($this->parameters as $name => $type) {
            $byReference = false;
            $type = $type ? $type.' ' : '';

            if ('&' === ($name[0] ?? null)) {
                $name = substr($name, 1);
                $byReference = true;
            }

            $argumentSources[] = sprintf('%s%s%s', $type, $byReference ? '&' : '', $compiler->subcompile(new VariableNode($name)));
        }

        $compiler->raw(implode(', ', $argumentSources));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
