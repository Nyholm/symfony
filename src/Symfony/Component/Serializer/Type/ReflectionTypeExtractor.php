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
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * Extracts type from PHP reflection.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class ReflectionTypeExtractor implements TypeExtractorInterface
{
    public function extractTypeFromProperty(\ReflectionProperty $property): Type
    {
        if (null === $type = $property->getType()) {
            throw new InvalidArgumentException(sprintf('Type of "%s::$%s" property has not been defined.', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        return $this->extractTypeFromReflection($type, $property->getDeclaringClass());
    }

    public function extractTypeFromFunctionReturn(\ReflectionFunctionAbstract $function): Type
    {
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $function->getReturnType()) {
            $path = null !== $declaringClass
                ? sprintf('%s::%s()', $declaringClass->getName(), $function->getName())
                : sprintf('%s()', $function->getName());

            throw new InvalidArgumentException(sprintf('Type of "%s" return value has not been defined.', $path));
        }

        return $this->extractTypeFromReflection($type, $declaringClass);
    }

    public function extractTypeFromParameter(\ReflectionParameter $parameter): Type
    {
        $function = $parameter->getDeclaringFunction();

        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $parameter->getType()) {
            $path = null !== $declaringClass
                ? sprintf('%s::%s($%s)', $declaringClass->getName(), $function->getName(), $parameter->getName())
                : sprintf('%s($%s)', $function->getName(), $parameter->getName());

            throw new InvalidArgumentException(sprintf('Type of "%s" parameter has not been defined.', $path));
        }

        return $this->extractTypeFromReflection($type, $declaringClass);
    }

    /**
     * @param \ReflectionClass<object>|null $declaringClass
     */
    private function extractTypeFromReflection(\ReflectionType $reflection, ?\ReflectionClass $declaringClass): Type
    {
        if (!($reflection instanceof \ReflectionUnionType || $reflection instanceof \ReflectionNamedType || $reflection instanceof \ReflectionIntersectionType)) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', (string) $reflection));
        }

        if ($reflection instanceof \ReflectionUnionType) {
            /** @var list<string> $unionTypes */
            $unionTypes = array_map(fn (\ReflectionNamedType $t): string => $this->extractTypeFromReflection($t, $declaringClass), $reflection->getTypes());

            return Type::fromString(implode('|', $unionTypes));
        }

        if ($reflection instanceof \ReflectionIntersectionType) {
            /** @var list<string> $intersectionTypes */
            $intersectionTypes = array_map(fn (\ReflectionNamedType $t): string => $this->extractTypeFromReflection($t, $declaringClass), $reflection->getTypes());

            return Type::fromString(implode('&', $intersectionTypes));
        }

        $nullablePrefix = $reflection->allowsNull() ? '?' : '';
        $phpTypeOrClass = $reflection->getName();

        if ('never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', $phpTypeOrClass));
        }

        if (\in_array($phpTypeOrClass, ['mixed', 'null'], true)) {
            return Type::fromString($phpTypeOrClass);
        }

        if ($declaringClass && 'self' === strtolower($phpTypeOrClass)) {
            $phpTypeOrClass = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($phpTypeOrClass) && $parent = $declaringClass->getParentClass()) {
            $phpTypeOrClass = $parent->name;
        }

        return Type::fromString($nullablePrefix.$phpTypeOrClass);
    }
}
