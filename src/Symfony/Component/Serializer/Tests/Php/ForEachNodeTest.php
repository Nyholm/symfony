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
use Symfony\Component\Serializer\Php\ForEachNode;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\ScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;

class ForEachNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<PhpNodeInterface> $body
     */
    public function testCompile(string $expectedSource, PhpNodeInterface $collection, ?VariableNode $keyName, VariableNode $valueName, array $body)
    {
        (new ForEachNode($collection, $keyName, $valueName, $body))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: PhpNodeInterface, 2: ?VariableNode, 3: VariableNode, 4: list<PhpNodeInterface>}>
     */
    public static function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            foreach (\$foo as \$fooValue) {
            }

            PHP,
            new VariableNode('foo'),
            null,
            new VariableNode('fooValue'),
            [],
        ];
        yield [
            <<<PHP
            foreach (\$foo as &\$fooValue) {
            }

            PHP,
            new VariableNode('foo'),
            null,
            new VariableNode('fooValue', byReference: true),
            [],
        ];
        yield [
            <<<PHP
            foreach (\$foo as \$fooKey => \$fooValue) {
            }

            PHP,
            new VariableNode('foo'),
            new VariableNode('fooKey'),
            new VariableNode('fooValue'),
            [],
        ];
        yield [
            <<<PHP
            foreach (\$foo as \$fooKey => \$fooValue) {
                "foo";
                "bar";
            }

            PHP,
            new VariableNode('foo'),
            new VariableNode('fooKey'),
            new VariableNode('fooValue'),
            [new ExpressionNode(new ScalarNode('foo')), new ExpressionNode(new ScalarNode('bar'))],
        ];
    }
}
