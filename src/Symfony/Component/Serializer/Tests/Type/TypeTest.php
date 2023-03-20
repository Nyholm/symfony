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
use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyUnitEnum;
use Symfony\Component\Serializer\Type\Type;

class TypeTest extends TestCase
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expectedString, Type $type)
    {
        $this->assertSame($expectedString, (string) $type);
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function toStringDataProvider(): iterable
    {
        yield ['null', Type::null()];
        yield ['int', Type::int()];
        yield ['string', Type::string()];
        yield ['float', Type::float()];
        yield ['bool', Type::bool()];
        yield ['?int', Type::int(nullable: true)];
        yield ['object', Type::object()];

        yield [ClassicDummy::class, Type::class(ClassicDummy::class)];
        yield ['?'.ClassicDummy::class, Type::class(ClassicDummy::class, nullable: true)];

        yield [ClassicDummy::class.'<int>', Type::class(ClassicDummy::class, genericParameterTypes: [Type::int()])];
        yield [
            ClassicDummy::class.'<'.ClassicDummy::class.'<?bool>>',
            Type::class(
                ClassicDummy::class,
                genericParameterTypes: [Type::class(ClassicDummy::class, genericParameterTypes: [Type::bool(nullable: true)])],
            ),
        ];

        yield ['array<int|string, mixed>', Type::array()];
        yield ['array<int, int>', Type::list(Type::int())];
        yield ['array<string, ?array<int, bool>>', Type::dict(Type::list(Type::bool(), nullable: true))];

        yield ['int|string', Type::union(Type::int(), Type::string())];
        yield ['int|array<int, int>|null', Type::union(Type::int(), Type::list(Type::int()), Type::null())];

        yield ['int&string', Type::intersection(Type::int(), Type::string())];
        yield ['int&array<int, int>&null', Type::intersection(Type::int(), Type::list(Type::int()), Type::null())];
    }

    public function testGetCollectionKeyType()
    {
        $this->assertEquals(Type::string(), Type::dict()->collectionKeyType());
        $this->assertEquals(Type::union(Type::int(), Type::string()), Type::array()->collectionKeyType());
    }

    public function testGetCollectionValueType()
    {
        $this->assertEquals(Type::int(), Type::list(Type::int())->collectionValueType());
        $this->assertEquals(Type::mixed(), Type::array()->collectionValueType());
    }

    /**
     * @dataProvider isserDataProvider
     */
    public function testIsser(
        Type $type,
        bool $scalar,
        bool $null,
        bool $nullable,
        bool $object,
        bool $enum,
        bool $collection,
        bool $list,
        bool $dict,
        bool $generic,
        bool $class,
        bool $union,
        bool $intersection,
    ) {
        $this->assertSame($scalar, $type->isScalar());
        $this->assertSame($null, $type->isNull());
        $this->assertSame($nullable, $type->isNullable());
        $this->assertSame($object, $type->isObject());
        $this->assertSame($enum, $type->isEnum());
        $this->assertSame($collection, $type->isCollection());
        $this->assertSame($list, $type->isList());
        $this->assertSame($dict, $type->isDict());
        $this->assertSame($generic, $type->isGeneric());
        $this->assertSame($class, $type->hasClass());
        $this->assertSame($union, $type->isUnion());
        $this->assertSame($intersection, $type->isIntersection());
    }

    /**
     * @return iterable<array<string, bool>>
     */
    public static function isserDataProvider(): iterable
    {
        yield [
            'type' => Type::int(),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::string(),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::bool(),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::float(),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::null(),
            'scalar' => true,
            'null' => true,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::array(),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => true,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::object(),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::class(ClassicDummy::class),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => true,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::enum(DummyBackedEnum::class),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => true,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::class(ClassicDummy::class, genericParameterTypes: [Type::int()]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => true,
            'class' => true,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::list(Type::int()),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => true,
            'list' => true,
            'dict' => false,
            'generic' => true,
            'class' => false,
            'union' => false,
            'intersection' => false,
        ];
        yield [
            'type' => Type::union(Type::int(), Type::float()),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
            'intersection' => false,
        ];
        yield [
            'type' => Type::union(Type::int(), Type::float(), Type::null()),
            'scalar' => true,
            'null' => false,
            'nullable' => true,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
            'intersection' => false,
        ];
        yield [
            'type' => Type::union(Type::int(), Type::array()),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
            'intersection' => false,
        ];
        yield [
            'type' => Type::union(Type::iterable(), Type::array()),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
            'intersection' => false,
        ];
        yield [
            'type' => Type::intersection(Type::int(), Type::float(), Type::null()),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => true,
        ];
        yield [
            'type' => Type::intersection(Type::int(), Type::array()),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => true,
        ];
        yield [
            'type' => Type::intersection(Type::iterable(), Type::array()),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'enum' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
            'intersection' => true,
        ];
    }

    /**
     * @dataProvider fromStringDataProvider
     */
    public function testFromString(Type $expectedType, string $string)
    {
        $this->assertEquals($expectedType, Type::fromString($string));
    }

    /**
     * @return iterable<array{0: Type, 1: string}>
     */
    public static function fromStringDataProvider(): iterable
    {
        yield [Type::null(), 'null'];
        yield [Type::int(), 'int'];
        yield [Type::string(nullable: true), '?string'];

        yield [Type::array(), 'array'];
        yield [Type::list(), 'list'];
        yield [Type::iterable(), 'iterable'];
        yield [Type::list(), 'array<mixed>'];
        yield [Type::iterableList(), 'iterable<mixed>'];

        yield [Type::class(ClassicDummy::class), ClassicDummy::class];
        yield [Type::enum(DummyBackedEnum::class), DummyBackedEnum::class];
        yield [Type::class(\DateTimeInterface::class), \DateTimeInterface::class];

        yield [Type::list(Type::dict(Type::bool(nullable: true))), 'list<array<string, ?bool>>'];
        yield [Type::list(Type::dict(Type::bool(nullable: true))), 'array<int, array<string, ?bool>>'];
        yield [Type::class(DummyWithGenerics::class, genericParameterTypes: [Type::int()]), DummyWithGenerics::class.'<int>'];

        yield [Type::union(Type::int(), Type::string()), 'int|string'];
        yield [Type::union(Type::int(), Type::string(), Type::null()), 'int|?string'];

        return;
        yield [Type::union(Type::int(), Type::dict(Type::bool())), 'int|array<string, bool>'];

        yield [Type::intersection(Type::int(), Type::string()), 'int&string'];
        yield [Type::intersection(Type::int(), Type::string(), Type::null()), 'int&?string'];
        yield [Type::intersection(Type::int(), Type::dict(Type::bool())), 'int&array<string, bool>'];
    }

    public function testFromStringThowOnDNF()
    {
        $this->expectException(InvalidArgumentException::class);
        Type::fromString('int|string&float');
    }

    /**
     * @dataProvider fromStringThowOnInvalidStringDataProvider
     */
    public function testFromStringThowOnInvalidString(string $string)
    {
        $this->expectException(InvalidArgumentException::class);

        Type::fromString($string);
    }

    /**
     * @return iterable<array{0: string}>
     */
    public function fromStringThowOnInvalidStringDataProvider()
    {
        yield ['int|string&float'];
        yield ['array<int'];
        yield ['array<int, array<int>>>'];
        yield ['array<int, string><int, string>'];
    }

    public function testEnumFactory()
    {
        $unitEnumType = Type::enum(DummyUnitEnum::class);

        $this->assertSame(DummyUnitEnum::class, $unitEnumType->className());
        $this->assertTrue($unitEnumType->isEnum());
        $this->assertFalse($unitEnumType->isBackedEnum());

        $backedEnumType = Type::enum(DummyBackedEnum::class);

        $this->assertSame(DummyBackedEnum::class, $backedEnumType->className());
        $this->assertEquals(Type::int(), $backedEnumType->backingType());
        $this->assertTrue($backedEnumType->isEnum());
        $this->assertTrue($backedEnumType->isBackedEnum());
    }
}
