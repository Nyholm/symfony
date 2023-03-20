<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Splitter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonSplitter;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

class JsonSplitterTest extends TestCase
{
    public function testSplitNull()
    {
        $this->assertNull((new JsonSplitter())->splitDict($this->createResource('null'), 0, -1));
        $this->assertNull((new JsonSplitter())->splitList($this->createResource('null'), 0, -1));
    }

    /**
     * @dataProvider splitDictDataProvider
     *
     * @param list<array{0: int, 1: int}> $expectedBoundaries
     */
    public function testSplitDict(array $expectedBoundaries, string $content)
    {
        $this->assertSame($expectedBoundaries, iterator_to_array((new JsonSplitter())->splitDict(self::createResource($content), 0, -1)));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: list<array{0: string, 1: int}>}>
     */
    public static function splitDictDataProvider(): iterable
    {
        yield [[], '{}'];
        yield [['k' => [5, 2]], '{"k":10}'];
        yield [['k' => [5, 4]], '{"k":[10]}'];
    }

    /**
     * @dataProvider splitListDataProvider
     *
     * @param list<array{0: int, 1: int}> $expectedBoundaries
     */
    public function testSplitList(array $expectedBoundaries, string $content)
    {
        $this->assertSame($expectedBoundaries, iterator_to_array((new JsonSplitter())->splitList(self::createResource($content), 0, -1)));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: string}>
     */
    public static function splitListDataProvider(): iterable
    {
        yield [[], '[]'];
        yield [[[1, 3]], '[100]'];
        yield [[[1, 3], [5, 3]], '[100,200]'];
        yield [[[1, 1], [3, 5]], '[1,[2,3]]'];
        yield [[[1, 1], [3, 5]], '[1,{2:3}]'];
    }

    /**
     * @dataProvider splitDictInvalidDataProvider
     */
    public function testSplitDictInvalidThrowException(string $content)
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonSplitter())->splitDict(self::createResource($content), 0, -1));
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: int}>}>
     */
    public static function splitDictInvalidDataProvider(): iterable
    {
        yield ['{100'];
        yield ['{{}'];
        yield ['{{}]'];
    }

    /**
     * @dataProvider splitListInvalidDataProvider
     */
    public function testSplitListInvalidThrowException(string $content)
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonSplitter())->splitList(self::createResource($content), 0, -1));
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function splitListInvalidDataProvider(): iterable
    {
        yield ['[100'];
        yield ['[[]'];
        yield ['[[]}'];
    }

    /**
     * @return resource
     */
    private static function createResource(string $content): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
