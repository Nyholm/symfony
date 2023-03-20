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
use Symfony\Component\Serializer\Php\ClosureNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ParametersNode;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class ClosureNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<PhpNodeInterface> $body
     * @param list<VariableNode>     $uses
     */
    public function testCompile(string $expectedSource, ParametersNode $arguments, ?string $returnType, bool $static, array $body, ?ArgumentsNode $uses)
    {
        (new ClosureNode($arguments, $returnType, $static, $body, $uses))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: ParametersNode, 2: ?string, 3: bool, 4: list<PhpNodeInterface>, 5: ?ArgumentsNode}>
     */
    public static function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            function (string \$foo) {
            }
            PHP,
            new ParametersNode(['foo' => 'string']),
            null,
            false,
            [],
            null,
        ];
        yield [
            <<<PHP
            static function (): string {
            }
            PHP,
            new ParametersNode([]),
            'string',
            true,
            [],
            null,
        ];
        yield [
            <<<PHP
            static function (): void {
                "foo";
                "bar";
            }
            PHP,
            new ParametersNode([]),
            'void',
            true,
            [new ExpressionNode(new ScalarNode('foo')), new ExpressionNode(new ScalarNode('bar'))],
            null,
        ];
        yield [
            <<<PHP
            function () use (\$foo, \$bar): string {
            }
            PHP,
            new ParametersNode([]),
            'string',
            false,
            [],
            new ArgumentsNode([new VariableNode('foo'), new VariableNode('bar')]),
        ];
    }
}
