<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Tests\Fixtures\Config\CustomDeserializeConfig;
use Symfony\Component\Serializer\Tests\Fixtures\Config\CustomSerializeConfig;

class DummyWithFormatterAttributes
{
    #[SerializeFormatter([self::class, 'doubleAndCastToString'])]
    #[DeserializeFormatter([self::class, 'divideAndCastToInt'])]
    public int $id = 1;

    public string $name = 'dummy';

    public static function doubleAndCastToString(int $value): string
    {
        return (string) (2 * $value);
    }

    public static function divideAndCastToInt(string $value): int
    {
        return (int) (((int) $value) / 2);
    }

    public static function doubleAndCastToStringWithConfig(int $value, CustomSerializeConfig $config): string
    {
        return (string) (2 * $config->scale() * $value);
    }

    public static function divideAndCastToIntWithConfig(string $value, CustomDeserializeConfig $config): int
    {
        return (int) (((int) $value) / (2 * $config->scale()));
    }
}
