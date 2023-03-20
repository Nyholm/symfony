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
use Symfony\Component\Serializer\Deserialize\Splitter\JsonLexer;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

class JsonLexerTest extends TestCase
{
    /**
     * @dataProvider tokensDataProvider
     *
     * @param list<string> $expectedTokens
     */
    public function testTokens(array $expectedTokens, string $content)
    {
        $this->assertSame($expectedTokens, iterator_to_array((new JsonLexer())->tokens($this->createResource($content), 0, -1)));
    }

    /**
     * @return iterable<array{0: list<string>, 1: string}>
     */
    public static function tokensDataProvider(): iterable
    {
        yield [[['1', 0]], '1'];
        yield [[['false', 0]], 'false'];
        yield [[['null', 0]], 'null'];
        yield [[['"string"', 0]], '"string"'];

        yield [[['[', 0], [']', 1]], '[]'];
        yield [[['[', 0], ['10', 2], [',', 4], ['20', 6], [']', 9]], '[ 10, 20 ]'];
        yield [[['[', 0], ['1', 1], [',', 2], ['[', 4], ['2', 5], [']', 6], [']', 8]], '[1, [2] ]'];

        yield [[['{', 0], ['}', 1]], '{}'];
        yield [[['{', 0], ['"foo"', 1], [':', 6], ['{', 8], ['"bar"', 9], [':', 14], ['"baz"', 15], ['}', 20], ['}', 21]], '{"foo": {"bar":"baz"}}'];
    }

    public function testTokensSubset()
    {
        $this->assertSame([['false', 7]], iterator_to_array((new JsonLexer())->tokens($this->createResource('[1, 2, false]'), 7, 5)));
    }

    public function testTokensThrowOnInvalidResource()
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonLexer())->tokens(fopen(sprintf('%s/%s', sys_get_temp_dir(), uniqid()), 'w'), 0, -1));
    }

    public function testTokenizeOverflowingBuffer()
    {
        $veryLongString = sprintf('"%s"', str_repeat('.', 20000));

        $this->assertSame([[$veryLongString, 0]], iterator_to_array((new JsonLexer())->tokens($this->createResource($veryLongString), 0, -1)));
    }

    /**
     * @return resource
     */
    private function createResource(string $content): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');

        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
