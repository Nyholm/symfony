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
use Symfony\Component\Serializer\Php\ArrayNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ScalarNode;

class ArrayNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ArrayNode([]))->compile($compiler = new Compiler());
        $this->assertSame('[]', $compiler->source());

        (new ArrayNode([new ScalarNode('foo'), new ScalarNode('bar')]))->compile($compiler = new Compiler());
        $this->assertSame('["foo", "bar"]', $compiler->source());

        (new ArrayNode(['foo' => new ScalarNode('foo'), 'bar' => new ScalarNode('bar')]))->compile($compiler = new Compiler());
        $this->assertSame('["foo" => "foo", "bar" => "bar"]', $compiler->source());

        (new ArrayNode([1 => new ScalarNode('foo'), 3 => new ScalarNode('bar')]))->compile($compiler = new Compiler());
        $this->assertSame('[1 => "foo", 3 => "bar"]', $compiler->source());
    }
}
