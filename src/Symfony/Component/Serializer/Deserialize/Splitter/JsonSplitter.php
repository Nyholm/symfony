<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Splitter;

use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class JsonSplitter implements SplitterInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];

    private static JsonLexer|null $lexer = null;

    /**
     * @var array{key: array<string, string>}
     */
    private static array $cache = [
        'key' => [],
    ];

    public static function splitList(mixed $resource, int $offset = 0, int $length = -1): ?\Iterator
    {
        $lexer = self::$lexer ??= new JsonLexer();
        $tokens = $lexer->tokens($resource, $offset, $length);

        if ('null' === $tokens->current()[0] && 1 === iterator_count($tokens)) {
            return null;
        }

        return self::createListBoundaries($tokens, $resource);
    }

    public static function splitDict(mixed $resource, int $offset = 0, int $length = -1): ?\Iterator
    {
        $lexer = self::$lexer ??= new JsonLexer();
        $tokens = $lexer->tokens($resource, $offset, $length);

        if ('null' === $tokens->current()[0] && 1 === iterator_count($tokens)) {
            return null;
        }

        return self::createDictBoundaries($tokens, $resource);
    }

    /**
     * @param \Iterator<array{0: string, 1: int}> $tokens
     * @param resource                            $resource
     *
     * @return \Iterator<array{0: int, 1: int}>
     */
    private static function createListBoundaries(\Iterator $tokens, mixed $resource): \Iterator
    {
        $level = 0;

        foreach ($tokens as $i => $token) {
            if (0 === $i) {
                continue;
            }

            $value = $token[0];
            $position = $token[1];
            $offset = $offset ?? $position;

            if (isset(self::NESTING_CHARS[$value])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$value])) {
                --$level;

                continue;
            }

            if (0 !== $level) {
                continue;
            }

            if (',' === $value) {
                if (($length = $position - $offset) > 0) {
                    yield [$offset, $length];
                }

                $offset = null;
            }
        }

        if (-1 !== $level || !isset($value, $offset, $position) || ']' !== $value) {
            throw new InvalidResourceException($resource);
        }

        if (($length = $position - $offset) > 0) {
            yield [$offset, $length];
        }
    }

    /**
     * @param \Iterator<array{0: string, 1: int}> $tokens
     * @param resource                            $resource
     *
     * @return \Iterator<string, array{0: int, 1: int}>
     */
    private static function createDictBoundaries(\Iterator $tokens, mixed $resource): \Iterator
    {
        $level = 0;
        $offset = 0;
        $firstValueToken = false;
        $key = null;

        foreach ($tokens as $i => $token) {
            if (0 === $i) {
                continue;
            }

            $value = $token[0];
            $position = $token[1];

            if ($firstValueToken) {
                $firstValueToken = false;
                $offset = $position;
            }

            if (isset(self::NESTING_CHARS[$value])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$value])) {
                --$level;

                continue;
            }

            if (0 !== $level) {
                continue;
            }

            if (':' === $value) {
                $firstValueToken = true;

                continue;
            }

            if (',' === $value) {
                if (null !== $key && ($length = $position - $offset) > 0) {
                    yield $key => [$offset, $length];
                }

                $key = null;

                continue;
            }

            if (null === $key) {
                $key = self::$cache['key'][$value] ??= json_decode($value);
            }
        }

        if (-1 !== $level || !isset($value, $position) || '}' !== $value) {
            throw new InvalidResourceException($resource);
        }

        if (null !== $key && ($length = $position - $offset) > 0) {
            yield $key => [$offset, $length];
        }
    }
}
