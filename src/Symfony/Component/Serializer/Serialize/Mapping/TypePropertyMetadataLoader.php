<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Mapping;

use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * Enhance properties serialization metadata based on properties' type.
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

    public function load(string $className, SerializeConfig $config, array $context): array
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
                    ->withFormatter(self::castDateTimeToString(...));
            }
        }

        return $result;
    }

    public static function castDateTimeToString(\DateTimeInterface $dateTime, SerializeConfig $config): string
    {
        return $dateTime->format($config->dateTimeFormat());
    }
}
