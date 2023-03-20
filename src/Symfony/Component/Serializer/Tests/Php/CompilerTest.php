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
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ScalarNode;

class CompilerTest extends TestCase
{
    public function testRaw()
    {
        $this->assertSame('rawString', (new Compiler())->raw('rawString')->source());
        $this->assertSame('rawString', (new Compiler())->indent()->raw('rawString')->source());
        $this->assertSame('    rawString', (new Compiler())->indent()->raw('rawString', indent: true)->source());
    }

    public function testLine()
    {
        $compiler = new Compiler();

        $this->assertSame("lineString\n", $compiler->line('lineString')->source());
        $this->assertSame("lineString\n    lineString\n", $compiler->indent()->line('lineString')->source());
        $this->assertSame("lineString\n    lineString\nlineString\n", $compiler->outdent()->line('lineString')->source());
    }

    public function testCompile()
    {
        $compiler = new Compiler();

        $this->assertSame("\"foo\";\n", $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();

        $this->assertSame("\"foo\";\n    \"bar\";\n", $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testReset()
    {
        $compiler = new Compiler();

        $this->assertSame("\"foo\";\n", $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();
        $compiler->reset();

        $this->assertSame("\"bar\";\n", $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testSubcompile()
    {
        $compiler = new Compiler();

        $this->assertSame("\"foo\";\n", $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());
        $this->assertSame("\"bar\";\n", $compiler->subcompile(new ExpressionNode(new ScalarNode('bar'))));

        $this->assertSame("\"foo\";\n", $compiler->source());
    }
}
