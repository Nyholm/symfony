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
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\FunctionCallNode;
use Symfony\Component\Serializer\Php\Optimizer;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class OptimizerTest extends TestCase
{
    /**
     * @dataProvider mergeStringFwritesDataProvider
     *
     * @param list<PhpNodeInterface> $expectedNodes
     * @param list<PhpNodeInterface> $nodes
     */
    public function testMergeStringFwrites(array $expectedNodes, array $nodes)
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<PhpNodeInterface>, 1: list<PhpNodeInterface>}>
     */
    public static function mergeStringFwritesDataProvider(): iterable
    {
        $createFwriteExpression = fn (PhpNodeInterface $content) => new ExpressionNode(new FunctionCallNode(
            '\fwrite',
            new ArgumentsNode([new VariableNode('resource'), $content]),
        ));

        yield [[
            $createFwriteExpression(new ScalarNode('foobar')),
        ], [
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            'foo' => $createFwriteExpression(new ScalarNode('foo')),
            'bar' => $createFwriteExpression(new ScalarNode('bar')),
        ], [
            'foo' => $createFwriteExpression(new ScalarNode('foo')),
            'bar' => $createFwriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new VariableNode('bar')),
            $createFwriteExpression(new ScalarNode('baz')),
        ], [
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new VariableNode('bar')),
            $createFwriteExpression(new ScalarNode('baz')),
        ]];

        yield [[
            new ExpressionNode(new FunctionCallNode('fooFunction', new ArgumentsNode([]))),
            new ExpressionNode(new FunctionCallNode('barFunction', new ArgumentsNode([]))),
            $createFwriteExpression(new ScalarNode('baz')),
        ], [
            new ExpressionNode(new FunctionCallNode('fooFunction', new ArgumentsNode([]))),
            new ExpressionNode(new FunctionCallNode('barFunction', new ArgumentsNode([]))),
            $createFwriteExpression(new ScalarNode('baz')),
        ]];
    }
}
