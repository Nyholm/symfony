<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ReflectionExtractableDummy;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;

class ReflectionTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(Type $expectedType, string $property)
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty($property);

        $this->assertEquals($expectedType, (new ReflectionTypeExtractor())->extractTypeFromProperty($reflectionProperty));
    }

    public function testThrowIfCannotFindPropertyType()
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty('undefined');

        $this->expectException(InvalidArgumentException::class);

        (new ReflectionTypeExtractor())->extractTypeFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromFunctionReturn(Type $expectedType, string $method)
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method);

        $this->assertEquals($expectedType, (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($reflectionMethod));
    }

    public function testExtractClassTypeFromFunctionReturnType()
    {
        $selfReflectionFunction = new \ReflectionFunction(function (): self {
            return $this;
        });

        $this->assertEquals(Type::class(self::class), (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($selfReflectionFunction));

        $parentReflectionFunction = new \ReflectionFunction(function (): parent {
            return $this;
        });

        $this->assertEquals(Type::class(parent::class), (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($parentReflectionFunction));
    }

    public function testCannotHandleVoidReturnType()
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('void');

        $this->expectException(UnsupportedException::class);

        (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($reflectionMethod);
    }

    public function testCannotHandleNeverReturnType()
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('never');

        $this->expectException(UnsupportedException::class);

        (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($reflectionMethod);
    }

    public function testThrowIfCannotFindReturnType()
    {
        $this->expectException(InvalidArgumentException::class);

        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined');

        (new ReflectionTypeExtractor())->extractTypeFromFunctionReturn($reflectionMethod);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(Type $expectedType, string $method)
    {
        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertEquals($expectedType, (new ReflectionTypeExtractor())->extractTypeFromParameter($reflectionParameter));
    }

    public function testThrowIfCannotFindParameterType()
    {
        $this->expectException(InvalidArgumentException::class);

        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined')->getParameters()[0];

        (new ReflectionTypeExtractor())->extractTypeFromParameter($reflectionParameter);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function typesDataProvider(): iterable
    {
        yield [Type::mixed(), 'mixed'];
        yield [Type::int(), 'int'];
        yield [Type::string(), 'string'];
        yield [Type::float(), 'float'];
        yield [Type::bool(), 'bool'];
        yield [Type::array(), 'array'];
        yield [Type::class(ReflectionExtractableDummy::class), 'self'];
        yield [Type::class(AbstractDummy::class), 'parent'];
        yield [Type::class(ClassicDummy::class), 'class'];
        yield [Type::union(Type::string(), Type::int()), 'union'];
        yield [Type::int(nullable: true), 'nullableBuiltin'];
        yield [Type::class(ClassicDummy::class, nullable: true), 'nullableClass'];
    }
}
