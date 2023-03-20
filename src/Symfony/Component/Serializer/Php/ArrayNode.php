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
final readonly class ArrayNode implements PhpNodeInterface
{
    /**
     * @param array<PhpNodeInterface> $elements
     */
    public function __construct(
        public array $elements,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw('[');

        $first = true;
        $associative = !array_is_list($this->elements);

        foreach ($this->elements as $key => $value) {
            if (!$first) {
                $compiler->raw(', ');
            }

            $first = false;

            if ($associative) {
                $compiler->compile(new ScalarNode($key))->raw(' => ');
            }

            $compiler->compile($value);
        }

        $compiler->raw(']');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->elements));
    }
}
