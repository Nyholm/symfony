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
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

class TypeGenericsHelperTest extends TestCase
{
    /**
     * @dataProvider classGenericTypesDataProvider
     *
     * @param array<string, Type> $expectedGenericTypes
     * @param class-string        $className
     */
    public function testClassGenericTypes(array $expectedGenericTypes, string $className, Type $type)
    {
        $typeGenericsHelper = new TypeGenericsHelper(new PhpstanTypeExtractor(new ReflectionTypeExtractor()));

        $this->assertEquals($expectedGenericTypes, $typeGenericsHelper->classGenericTypes($className, $type));
    }

    /**
     * @return iterable<array{0: array<string, Type>, 1: class-string, 2: Type}>
     */
    public static function classGenericTypesDataProvider(): iterable
    {
        yield [[], ClassicDummy::class, Type::class(ClassicDummy::class)];
        yield [[], ClassicDummy::class, Type::generic(Type::class(DummyWithGenerics::class), Type::int())];
        yield [['T' => Type::int()], DummyWithGenerics::class, Type::generic(Type::class(DummyWithGenerics::class), Type::int())];
        yield [['T' => Type::int()], DummyWithGenerics::class, Type::list(Type::generic(Type::class(DummyWithGenerics::class), Type::int()))];
        yield [['T' => Type::int()], DummyWithGenerics::class, Type::union(Type::int(), Type::generic(Type::class(DummyWithGenerics::class), Type::int()))];
        yield [['T' => Type::int()], DummyWithGenerics::class, Type::intersection(Type::int(), Type::generic(Type::class(DummyWithGenerics::class), Type::int()))];
    }

    /**
     * @dataProvider replaceGenericTypesDataProvider
     *
     * @param array<string, Type> $genericTypes
     */
    public function testReplaceGenericTypes(Type $expectedType, Type $type, array $genericTypes)
    {
        $this->assertEquals($expectedType, (new TypeGenericsHelper($this->createStub(TypeExtractorInterface::class)))->replaceGenericTypes($type, $genericTypes));
    }

    public function testThrowWhenGenericParametersDoesNotMatchTemplate()
    {
        $this->expectException(InvalidArgumentException::class);

        $typeGenericsHelper = new TypeGenericsHelper(new PhpstanTypeExtractor(new ReflectionTypeExtractor()));
        $typeGenericsHelper->classGenericTypes(DummyWithGenerics::class, Type::class(DummyWithGenerics::class));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, string>}>
     */
    public static function replaceGenericTypesDataProvider(): iterable
    {
        yield [Type::fromString('T'), Type::fromString('T'), []];
        yield [Type::fromString('Foo'), Type::fromString('T'), ['T' => Type::fromString('Foo')]];

        yield [Type::list(Type::fromString('Foo')), Type::list(Type::fromString('T')), ['T' => Type::fromString('Foo')]];
        yield [Type::array(Type::fromString('Foo'), Type::fromString('Foo')), Type::array(Type::fromString('T'), Type::fromString('T')), ['T' => Type::fromString('Foo')]];

        yield [Type::union(Type::int(), Type::fromString('Foo')), Type::union(Type::int(), Type::fromString('T')), ['T' => Type::fromString('Foo')]];
        yield [
            Type::union(Type::int(), Type::fromString('Foo'), Type::fromString('Bar')),
            Type::union(Type::int(), Type::fromString('T'), Type::fromString('U')),
            ['T' => Type::fromString('Foo'), 'U' => Type::fromString('Bar')],
        ];
        yield [
            Type::union(Type::int(), Type::fromString('Foo'), Type::dict(Type::fromString('Bar'))),
            Type::union(Type::int(), Type::fromString('T'), Type::dict(Type::fromString('U'))),
            ['T' => Type::fromString('Foo'), 'U' => Type::fromString('Bar')],
        ];

        yield [Type::intersection(Type::int(), Type::fromString('Foo')), Type::intersection(Type::int(), Type::fromString('T')), ['T' => Type::fromString('Foo')]];
        yield [
            Type::intersection(Type::int(), Type::fromString('Foo'), Type::fromString('Bar')),
            Type::intersection(Type::int(), Type::fromString('T'), Type::fromString('U')),
            ['T' => Type::fromString('Foo'), 'U' => Type::fromString('Bar')],
        ];
        yield [
            Type::intersection(Type::int(), Type::fromString('Foo'), Type::dict(Type::fromString('Bar'))),
            Type::intersection(Type::int(), Type::fromString('T'), Type::dict(Type::fromString('U'))),
            ['T' => Type::fromString('Foo'), 'U' => Type::fromString('Bar')],
        ];
    }
}
