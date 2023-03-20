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
final class JsonLexer
{
    private const MAX_CHUNK_LENGTH = 8192;

    private const WHITESPACE_CHARS = [' ' => true, "\r" => true, "\t" => true, "\n" => true];
    private const STRUCTURE_CHARS = [',' => true, ':' => true, '{' => true, '}' => true, '[' => true, ']' => true];

    /**
     * @param resource $resource
     *
     * @return \Iterator<array{0: string, 1: int}>
     *
     * @throws InvalidResourceException
     */
    public function tokens(mixed $resource, int $offset, int $length): \Iterator
    {
        $currentTokenPosition = $offset;

        $token = '';

        $inString = $escaping = false;
        $infiniteLength = -1 === $length;
        $chunkLength = $infiniteLength ? self::MAX_CHUNK_LENGTH : min($length, self::MAX_CHUNK_LENGTH);

        if (false === @rewind($resource)) {
            throw new InvalidResourceException($resource);
        }

        $toReadLength = $length;

        while (!feof($resource) && ($infiniteLength || $toReadLength > 0)) {
            if (!$buffer = @stream_get_contents($resource, $infiniteLength ? -1 : min($chunkLength, $toReadLength), $offset)) {
                throw new InvalidResourceException($resource);
            }

            $toReadLength -= $bufferLength = \strlen($buffer);

            for ($i = 0; $i < $bufferLength; ++$i) {
                $byte = $buffer[$i];

                if ($escaping) {
                    $escaping = false;
                    $token .= $byte;

                    continue;
                }

                if ($inString) {
                    $token .= $byte;

                    if ('"' === $byte) {
                        $inString = false;
                    } elseif ('\\' === $byte) {
                        $escaping = true;
                    }

                    continue;
                }

                if ('"' === $byte) {
                    $token .= $byte;
                    $inString = true;

                    continue;
                }

                if (isset(self::STRUCTURE_CHARS[$byte]) || isset(self::WHITESPACE_CHARS[$byte])) {
                    if ('' !== $token) {
                        yield [$token, $currentTokenPosition];

                        $currentTokenPosition += \strlen($token);
                        $token = '';
                    }

                    if (!isset(self::WHITESPACE_CHARS[$byte])) {
                        yield [$byte, $currentTokenPosition];
                    }

                    if ('' !== $byte) {
                        ++$currentTokenPosition;
                    }

                    continue;
                }

                $token .= $byte;
            }

            $offset += $bufferLength;
        }

        if ('' !== $token) {
            yield [$token, $currentTokenPosition];
        }
    }
}
