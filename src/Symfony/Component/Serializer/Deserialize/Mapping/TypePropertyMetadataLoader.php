<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;

use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * Enhance properties deserialization metadata based on properties' type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly PropertyMetadataLoaderInterface $decorated,
        TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper($typeExtractor);
    }

    public function load(string $className, DeserializeConfig $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);
        $genericTypes = $this->typeGenericsHelper->classGenericTypes($className, $context['original_type']);

        foreach ($result as &$metadata) {
            $type = $metadata->type();

            if (isset($genericTypes[(string) $type])) {
                $metadata = $metadata->withType($this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes));
                $type = $metadata->type();
            }

            if ($type->isObject() && $type->hasClass() && is_a($type->className(), \DateTimeInterface::class, true)) {
                $metadata = $metadata
                    ->withType(Type::string())
                    ->withFormatter(self::castStringToDateTime(...));
            }
        }

        return $result;
    }

    public static function castStringToDateTime(string $string, DeserializeConfig $config): \DateTimeInterface
    {
        if (false !== $dateTime = \DateTimeImmutable::createFromFormat($config->dateTimeFormat(), $string)) {
            return $dateTime;
        }

        return new \DateTimeImmutable($string);
    }
}
