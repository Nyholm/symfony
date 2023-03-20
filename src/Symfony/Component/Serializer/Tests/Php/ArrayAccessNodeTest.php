<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Php;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class ArrayAccessNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ArrayAccessNode(new VariableNode('foo'), new ScalarNode('bar')))->compile($compiler = new Compiler());
        $this->assertSame('$foo["bar"]', $compiler->source());

        (new ArrayAccessNode(new VariableNode('foo'), key: null))->compile($compiler = new Compiler());
        $this->assertSame('$foo[]', $compiler->source());
    }
}
