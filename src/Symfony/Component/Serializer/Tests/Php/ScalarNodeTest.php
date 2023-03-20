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
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ScalarNode;

class ScalarNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(string $expectedSource, mixed $scalar)
    {
        (new ScalarNode($scalar))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: mixed}>
     */
    public static function compileDataProvider(): iterable
    {
        yield ['null', null];
        yield ['123', 123];
        yield ['123.456', 123.456];
        yield ['true', true];
        yield ['false', false];
        yield ['"string"', 'string'];
        yield ['"\"string\""', '"string"'];
        yield ['"str\\\\ing"', 'str\ing'];
    }

    public function testCannotCompileNotScalar()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value is not a scalar. Got "array".');

        (new ScalarNode(['foo']))->compile($compiler = new Compiler());
    }
}
