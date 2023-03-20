<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Decoder;

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Encoder\CsvEncoder as LegacyCsvDecoder;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvDecoder implements DecoderInterface
{
    private static LegacyCsvDecoder|null $legacyCsvDecoder = null;

    public static function decode(mixed $resource, int $offset, int $length, DeserializeConfig $config): mixed
    {
        if ('' === $content = @stream_get_contents($resource, $length, $offset)) {
            throw new InvalidResourceException($resource);
        }

        $csvConfig = $config->csv();

        $legacyContext = [
            LegacyCsvDecoder::DELIMITER_KEY => $csvConfig->delimiter(),
            LegacyCsvDecoder::ENCLOSURE_KEY => $csvConfig->enclosure(),
            LegacyCsvDecoder::ESCAPE_CHAR_KEY => $csvConfig->escapeChar(),
            LegacyCsvDecoder::END_OF_LINE => $csvConfig->endOfLine(),
            LegacyCsvDecoder::ESCAPE_FORMULAS_KEY => $csvConfig->escapedFormulas(),
            LegacyCsvDecoder::HEADERS_KEY => $csvConfig->headers(),
            LegacyCsvDecoder::KEY_SEPARATOR_KEY => $csvConfig->keySeparator(),
            LegacyCsvDecoder::NO_HEADERS_KEY => $csvConfig->noHeaders(),
            LegacyCsvDecoder::AS_COLLECTION_KEY => $csvConfig->asCollection(),
            LegacyCsvDecoder::OUTPUT_UTF8_BOM_KEY => $csvConfig->utf8Bom(),
        ];

        $legacyCsvDecoder = self::$legacyCsvDecoder ??= new LegacyCsvDecoder();

        return $legacyCsvDecoder->decode($content, 'csv', $legacyContext);
    }
}
