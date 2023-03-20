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
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class MethodCallNodeTest extends TestCase
{
    public function testCompile()
    {
        (new MethodCallNode(new VariableNode('object'), 'method', new ArgumentsNode([new VariableNode('foo'), new ScalarNode(true)])))->compile($compiler = new Compiler());
        $this->assertSame('$object->method($foo, true)', $compiler->source());

        (new MethodCallNode(new VariableNode('object'), 'method', new ArgumentsNode([]), true))->compile($compiler = new Compiler());
        $this->assertSame('$object::method()', $compiler->source());
    }
}
