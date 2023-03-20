<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Encoder;

use Symfony\Component\Serializer\Encoder\CsvEncoder as LegacyCsvEncoder;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvEncoder implements EncoderInterface
{
    private static LegacyCsvEncoder|null $legacyCsvEncoder = null;

    public static function encode(mixed $resource, mixed $normalized, SerializeConfig $config): void
    {
        $csvConfig = $config->csv();

        $legacyContext = [
            LegacyCsvEncoder::DELIMITER_KEY => $csvConfig->delimiter(),
            LegacyCsvEncoder::ENCLOSURE_KEY => $csvConfig->enclosure(),
            LegacyCsvEncoder::ESCAPE_CHAR_KEY => $csvConfig->escapeChar(),
            LegacyCsvEncoder::END_OF_LINE => $csvConfig->endOfLine(),
            LegacyCsvEncoder::ESCAPE_FORMULAS_KEY => $csvConfig->escapedFormulas(),
            LegacyCsvEncoder::HEADERS_KEY => $csvConfig->headers(),
            LegacyCsvEncoder::KEY_SEPARATOR_KEY => $csvConfig->keySeparator(),
            LegacyCsvEncoder::NO_HEADERS_KEY => $csvConfig->noHeaders(),
            LegacyCsvEncoder::AS_COLLECTION_KEY => $csvConfig->asCollection(),
            LegacyCsvEncoder::OUTPUT_UTF8_BOM_KEY => $csvConfig->utf8Bom(),
        ];

        $legacyCsvEncoder = self::$legacyCsvEncoder ??= new LegacyCsvEncoder();

        fwrite($resource, $legacyCsvEncoder->encode($normalized, 'csv', $legacyContext));
    }
}
