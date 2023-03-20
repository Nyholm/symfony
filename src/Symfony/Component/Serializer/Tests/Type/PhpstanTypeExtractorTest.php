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
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\NonUniqueTemplatePhpstanExtractableDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\PhpstanExtractableDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

class PhpstanTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(Type $expectedType, string $property)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromProperty')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty($property);

        $this->assertEquals($expectedType, $extractor->extractTypeFromProperty($reflectionProperty));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromFunctionReturn(Type $expectedType, string $method)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromFunctionReturn')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method);

        $this->assertEquals($expectedType, $extractor->extractTypeFromFunctionReturn($reflectionMethod));
    }

    public function testFallbackOnVoidAndNeverFunctionReturn()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromFunctionReturn')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $voidReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('void');
        $neverReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('never');

        $this->assertEquals(Type::fromString('FALLBACK'), $extractor->extractTypeFromFunctionReturn($voidReflectionMethod));
        $this->assertEquals(Type::fromString('FALLBACK'), $extractor->extractTypeFromFunctionReturn($neverReflectionMethod));
    }

    public function testExtractClassTypeFromFunctionFunctionReturn()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromFunctionReturn')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @return self */
        $selfReflectionFunction = new \ReflectionFunction(function () {
            return $this;
        });

        $this->assertEquals(Type::class(self::class), $extractor->extractTypeFromFunctionReturn($selfReflectionFunction));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(Type $expectedType, string $method)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromParameter')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionParameter = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertEquals($expectedType, $extractor->extractTypeFromParameter($reflectionParameter));
    }

    public function testExtractClassTypeFromParameter()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractTypeFromParameter')->willReturn(Type::fromString('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @param self $_ */
        $selfReflectionFunction = new \ReflectionFunction(function ($_) {
        });

        $this->assertEquals(Type::class(self::class), $extractor->extractTypeFromParameter($selfReflectionFunction->getParameters()[0]));
    }

    /**
     * @return iterable<array{0: Type, 1: string}>
     */
    public static function typesDataProvider(): iterable
    {
        yield [Type::mixed(), 'mixed'];
        yield [Type::bool(), 'bool'];
        yield [Type::bool(), 'boolean'];
        yield [Type::bool(), 'true'];
        yield [Type::bool(), 'false'];
        yield [Type::int(), 'int'];
        yield [Type::int(), 'integer'];
        yield [Type::float(), 'float'];
        yield [Type::string(), 'string'];
        yield [Type::resource(), 'resource'];
        yield [Type::object(), 'object'];
        yield [Type::callable(), 'callable'];
        yield [Type::array(), 'array'];
        yield [Type::list(), 'list'];
        yield [Type::array(), 'nonEmptyArray'];
        yield [Type::list(), 'nonEmptyList'];
        yield [Type::iterable(), 'iterable'];
        yield [Type::null(), 'null'];
        yield [Type::class(PhpstanExtractableDummy::class), 'self'];
        yield [Type::class(PhpstanExtractableDummy::class), 'static'];
        yield [Type::class(AbstractDummy::class), 'parent'];
        yield [Type::fromString('Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\scoped'), 'scoped'];
        yield [Type::enum(DummyBackedEnum::class), 'use'];
        yield [Type::class(ClassicDummy::class), 'sameNamespace'];
        yield [Type::int(nullable: true), 'nullable'];
        yield [Type::union(Type::int(), Type::string()), 'union'];
        yield [Type::intersection(Type::int(), Type::string()), 'intersection'];
        yield [Type::list(Type::string()), 'genericList'];
        yield [Type::list(Type::string()), 'genericArrayList'];
        yield [Type::dict(Type::string()), 'genericDict'];
        yield [Type::list(Type::string()), 'squareBracketList'];
        yield [Type::dict(Type::union(Type::int(), Type::string())), 'bracketList'];
        yield [Type::dict(), 'emptyBracketList'];
        yield [Type::class(\ArrayIterator::class, genericParameterTypes: [Type::fromString('Tk'), Type::fromString('Tv')]), 'generic'];
        yield [Type::fromString('Tv'), 'genericParameter'];
        yield [Type::fromString('FALLBACK'), 'undefined'];
    }

    public function testExtractTemplateFromClass()
    {
        $extractor = new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class));

        $this->assertSame(['Tk', 'Tv'], $extractor->extractTemplateFromClass(new \ReflectionClass(PhpstanExtractableDummy::class)));
    }

    public function testExtractTemplateFromClassThrowWhenNonUniqueTemplate()
    {
        $extractor = new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class));

        $this->expectException(UnexpectedValueException::class);

        $extractor->extractTemplateFromClass(new \ReflectionClass(NonUniqueTemplatePhpstanExtractableDummy::class));
    }
}
