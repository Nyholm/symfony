<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Type;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TypeGenericsHelper
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @return array<string, Type>
     */
    public function classGenericTypes(string $className, Type $type): array
    {
        if (!$this->typeExtractor instanceof TemplateExtractorInterface) {
            return [];
        }

        $findClassType = static function (string $className, Type $type) use (&$findClassType): ?Type {
            if ($type->hasClass() && $type->className() === $className) {
                return $type;
            }

            foreach ($type->genericParameterTypes() as $genericParameterType) {
                if (null !== $t = $findClassType($className, $genericParameterType)) {
                    return $t;
                }
            }

            foreach ($type->unionTypes() as $unionType) {
                if (null !== $t = $findClassType($className, $unionType)) {
                    return $t;
                }
            }

            foreach ($type->intersectionTypes() as $intersectionType) {
                if (null !== $t = $findClassType($className, $intersectionType)) {
                    return $t;
                }
            }

            return null;
        };

        $classType = $findClassType($className, $type);
        if (null === $classType) {
            return [];
        }

        $genericParameterTypes = $classType->genericParameterTypes();
        $templates = $this->typeExtractor->extractTemplateFromClass(new \ReflectionClass($className));

        if (\count($templates) !== \count($genericParameterTypes)) {
            throw new InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%2$s".', \count($genericParameterTypes), $className, \count($templates)));
        }

        $genericTypes = [];
        foreach ($genericParameterTypes as $i => $genericParameterType) {
            $genericTypes[$templates[$i]] = $genericParameterType;
        }

        return $genericTypes;
    }

    /**
     * @param array<string, Type> $genericTypes
     */
    public function replaceGenericTypes(Type $type, array $genericTypes): Type
    {
        $typeString = (string) $type;

        if (isset($genericTypes[$typeString])) {
            return Type::fromString($genericTypes[$typeString]);
        }

        if ([] !== $type->genericParameterTypes()) {
            return Type::fromString(sprintf(
                '%s<%s>',
                $type->name(),
                implode(', ', array_map(fn (Type $t): string => $this->replaceGenericTypes($t, $genericTypes), $type->genericParameterTypes())),
            ));
        }

        if ([] !== $type->unionTypes()) {
            return Type::fromString(implode('|', array_map(fn (Type $t): string => $this->replaceGenericTypes($t, $genericTypes), $type->unionTypes())));
        }

        if ([] !== $type->intersectionTypes()) {
            return Type::fromString(implode('&', array_map(fn (Type $t): string => $this->replaceGenericTypes($t, $genericTypes), $type->intersectionTypes())));
        }

        return Type::fromString($typeString);
    }
}
