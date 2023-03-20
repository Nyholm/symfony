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
use Symfony\Component\Serializer\Php\TemplateStringNode;
use Symfony\Component\Serializer\Php\VariableNode;

class TemplateStringNodeTest extends TestCase
{
    /**
     * @param list<string|VariableNode> $parts
     *
     * @dataProvider compileDataProvider
     */
    public function testCompile(string $expectedSource, array $parts)
    {
        (new TemplateStringNode(...$parts))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: list<string|VariableNode>}>
     */
    public static function compileDataProvider(): iterable
    {
        yield ['""', []];
        yield ['"foobar"', ['foo', 'bar']];
        yield ['"foo{$bar}baz"', ['foo', new VariableNode('bar'), 'baz']];
    }
}
