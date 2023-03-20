<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

class DummyWithAttributesUsingServices
{
    #[SerializeFormatter([self::class, 'serviceAndSerializeConfig'])]
    #[DeserializeFormatter([self::class, 'serviceAndDeserializeConfig'])]
    public string $one = 'one';

    #[SerializeFormatter([self::class, 'autowireAttribute'])]
    #[MaxDepth(1, [self::class, 'invalidNullableService'])]
    public string $two = 'two';

    #[SerializeFormatter([self::class, 'invalidOptionalService'])]
    #[MaxDepth(1, [self::class, 'skippedUnknownService'])]
    public string $three = 'three';

    public static function serviceAndSerializeConfig(string $value, SerializerInterface $serializer, TypeExtractorInterface $typeExtractor, SerializeConfig $config): string
    {
        return 'useless';
    }

    public static function serviceAndDeserializeConfig(string $value, TypeExtractorInterface $service, DeserializeConfig $config): string
    {
        return 'useless';
    }

    public static function autowireAttribute(string $value, #[Autowire(service: 'serializer.type_extractor')] $service): string
    {
        return 'useless';
    }

    public static function invalidNullableService(string $value, ?\InvalidInterface $invalid): string
    {
        return 'useless';
    }

    public static function invalidOptionalService(string $value, \InvalidInterface $invalid = null): string
    {
        return 'useless';
    }

    public static function skippedUnknownService(string $value, $skipped): string
    {
        return 'useless';
    }
}
