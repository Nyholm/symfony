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
use Symfony\Component\Serializer\Php\IfNode;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class IfNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<PhpNodeInterface>                                                 $onIf
     * @param list<PhpNodeInterface>                                                 $onElse
     * @param list<array{condition: PhpNodeInterface, body: list<PhpNodeInterface>}> $elseIfs
     */
    public function testCompile(string $expectedSource, PhpNodeInterface $condition, array $onIf, array $onElse, array $elseIfs)
    {
        (new IfNode($condition, $onIf, $onElse, $elseIfs))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: PhpNodeInterface, 2: list<PhpNodeInterface>, 3: list<PhpNodeInterface>, 4: list<array{condition: PhpNodeInterface, body: list<PhpNodeInterface>}>}>
     */
    public static function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            if (\$foo) {
                "onIf";
            }

            PHP,
            new VariableNode('foo'),
            [new ExpressionNode(new ScalarNode('onIf'))],
            [],
            [],
        ];
        yield [
            <<<PHP
            if (\$foo) {
                "onIf";
            } else {
                "onElse";
            }

            PHP,
            new VariableNode('foo'),
            [new ExpressionNode(new ScalarNode('onIf'))],
            [new ExpressionNode(new ScalarNode('onElse'))],
            [],
        ];
        yield [
            <<<PHP
            if (\$foo) {
            } elseif (\$elseIfOne) {
                "onElseIfOne";
            } elseif (\$elseIfTwo) {
                "onElseIfTwo";
            }

            PHP,
            new VariableNode('foo'),
            [],
            [],
            [
                ['condition' => new VariableNode('elseIfOne'), 'body' => [new ExpressionNode(new ScalarNode('onElseIfOne'))]],
                ['condition' => new VariableNode('elseIfTwo'), 'body' => [new ExpressionNode(new ScalarNode('onElseIfTwo'))]],
            ],
        ];
    }
}
