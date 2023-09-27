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
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Type implements \Stringable
{
    /**
     * @var array<string, Type>
     */
    private static array $cache = [];

    /**
     * @var class-string|null
     */
    private readonly ?string $className;

    private readonly string $stringValue;

    /**
     * @param class-string|null $className
     * @param list<self>        $genericParameterTypes
     * @param list<self>        $unionTypes
     * @param list<self>        $intersectionTypes
     */
    private function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        string $className = null,
        private readonly array $genericParameterTypes = [],
        private readonly array $unionTypes = [],
        private readonly array $intersectionTypes = [],
        private readonly ?self $backingType = null,
    ) {
        if (1 === \count($this->unionTypes)) {
            throw new InvalidArgumentException(sprintf('Cannot define only one union type for "%s" type.', $this->name));
        }

        if (1 === \count($this->intersectionTypes)) {
            throw new InvalidArgumentException(sprintf('Cannot define only one intersection type for "%s" type.', $this->name));
        }

        if ('stdClass' === $className) {
            $className = null;
        }

        $this->className = $className;
        $this->stringValue = $this->computeStringValue();
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        if (!$this->isObject() && !$this->isEnum()) {
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an object nor an enum.', $this->name));
        }

        if (null === $this->className) {
            throw new LogicException(sprintf('No class has been defined for "%s".', $this->name));
        }

        return $this->className;
    }

    /**
     * @return list<self>
     */
    public function genericParameterTypes(): array
    {
        return $this->genericParameterTypes;
    }

    /**
     * @return list<self>
     */
    public function unionTypes(): array
    {
        return $this->unionTypes;
    }

    /**
     * @return list<self>
     */
    public function intersectionTypes(): array
    {
        return $this->intersectionTypes;
    }

    public function backingType(): self
    {
        if (!$this->isEnum()) {
            throw new LogicException(sprintf('Cannot get backing type on "%s" type as it\'s not an enum.', $this->name));
        }

        if (null === $this->backingType) {
            throw new LogicException(sprintf('No backing type has been defined for "%s".', $this->name));
        }

        return $this->backingType;
    }

    public function isScalar(): bool
    {
        if ($this->isUnion()) {
            return array_reduce($this->unionTypes, fn (bool $c, self $t): bool => $c && $t->isScalar(), true);
        }

        if ($this->isIntersection()) {
            foreach ($this->intersectionTypes as $type) {
                if ($type->isScalar()) {
                    return true;
                }
            }

            return false;
        }

        return \in_array($this->name, ['int', 'float', 'string', 'bool', 'null'], true);
    }

    public function isNull(): bool
    {
        return 'null' === $this->name;
    }

    public function isNullable(): bool
    {
        if ($this->isUnion()) {
            foreach ($this->unionTypes as $type) {
                if ($type->isNull() || $type->isNullable()) {
                    return true;
                }
            }

            return false;
        }

        if ($this->isIntersection()) {
            return array_reduce($this->intersectionTypes, fn (bool $c, self $t): bool => $c && $t->isNullable(), true);
        }

        return $this->isNullable;
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function hasClass(): bool
    {
        return null !== $this->className;
    }

    public function isEnum(): bool
    {
        return 'enum' === $this->name;
    }

    public function isBackedEnum(): bool
    {
        return $this->isEnum() && null !== $this->backingType;
    }

    public function isGeneric(): bool
    {
        return [] !== $this->genericParameterTypes;
    }

    public function isUnion(): bool
    {
        return [] !== $this->unionTypes;
    }

    public function isIntersection(): bool
    {
        return [] !== $this->intersectionTypes;
    }

    public function isCollection(): bool
    {
        if ($this->isUnion()) {
            return array_reduce($this->unionTypes, fn (bool $c, self $t): bool => $c && $t->isCollection(), true);
        }

        if ($this->isIntersection()) {
            foreach ($this->intersectionTypes as $type) {
                if ($type->isCollection()) {
                    return true;
                }
            }

            return false;
        }

        return \in_array($this->name, ['array', 'iterable'], true);
    }

    public function isIterable(): bool
    {
        return 'iterable' === $this->name;
    }

    public function isList(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'int' === $collectionKeyType->name();
    }

    public function isDict(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'string' === $collectionKeyType->name();
    }

    public function collectionKeyType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[0] ?? new self('mixed');
    }

    public function collectionValueType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[1] ?? new self('mixed');
    }

    public function __toString(): string
    {
        return $this->stringValue;
    }

    public static function fromString(string $string): self
    {
        if (isset(self::$cache[$cacheKey = $string])) {
            return self::$cache[$cacheKey];
        }

        if ('null' === $string) {
            return self::$cache[$cacheKey] = new self('null');
        }

        if ($isNullable = str_starts_with($string, '?')) {
            $string = substr($string, 1);
        }

        if (\in_array($string, ['int', 'string', 'float', 'bool'])) {
            return self::$cache[$cacheKey] = new self($string, $isNullable);
        }

        $string = match ($string) {
            'array' => 'array<int|string, mixed>',
            'list' => 'array<int, mixed>',
            'iterable' => 'iterable<int|string, mixed>',
            default => $string,
        };

        if (is_subclass_of($string, \UnitEnum::class)) {
            $reflection = new \ReflectionEnum($string);

            if ($reflection->isBacked() && null !== ($backingType = $reflection->getBackingType())) {
                return self::$cache[$cacheKey] = new self('enum', $isNullable, $string, backingType: new self((string) $backingType));
            }

            return self::$cache[$cacheKey] = new self('enum', $isNullable, $string);
        }

        if (class_exists($string) || interface_exists($string)) {
            return self::$cache[$cacheKey] = new self('object', $isNullable, $string);
        }

        $currentTypeString = '';
        $typeStrings = [];
        $typesGlue = null;
        $nestedLevel = 0;

        foreach (str_split(str_replace(' ', '', $string)) as $char) {
            if ('<' === $char) {
                ++$nestedLevel;
            }

            if ('>' === $char) {
                --$nestedLevel;
            }

            if (\in_array($char, ['|', '&'], true) && 0 === $nestedLevel) {
                if (null !== $typesGlue && $char !== $typesGlue) {
                    throw new UnsupportedException(sprintf('"%s" DNF type is not supported.', $string));
                }

                $typeStrings[] = $currentTypeString;
                $typesGlue = $char;
                $currentTypeString = '';

                continue;
            }

            $currentTypeString .= $char;
        }

        $typeStrings[] = $currentTypeString;

        if (0 !== $nestedLevel) {
            throw new InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
        }

        if (\count($typeStrings) > 1) {
            $nullable = false;
            $types = [];

            foreach ($typeStrings as $typeString) {
                if (str_starts_with($typeString, '?')) {
                    $nullable = true;
                    $typeString = substr($typeString, 1);
                }

                if ('null' === $typeString) {
                    $nullable = true;

                    continue;
                }

                $type = self::fromString($typeString);
                $types[] = $type;
            }

            if ($nullable) {
                $types[] = new self('null');
            }

            if ('&' === $typesGlue) {
                return self::$cache[$cacheKey] = new self(implode('&', array_map(fn (Type $t): string => $t, $types)), intersectionTypes: $types);
            }

            return self::$cache[$cacheKey] = new self(implode('|', array_map(fn (Type $t): string => $t, $types)), unionTypes: $types);
        }

        $results = [];
        if (preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $string, $results)) {
            $genericType = $results['type'];
            $genericParameters = [];
            $currentGenericParameter = '';
            $nestedLevel = 0;
            $chars = str_split(str_replace(' ', '', $results['diamond']));

            foreach ($chars as $i => $char) {
                if (',' === $char && 0 === $nestedLevel) {
                    $genericParameters[] = $currentGenericParameter;
                    $currentGenericParameter = '';

                    continue;
                }

                if ('<' === $char) {
                    ++$nestedLevel;
                }

                if ('>' === $char) {
                    if (0 === $nestedLevel && $i !== \count($chars) - 1) {
                        throw new InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
                    }

                    --$nestedLevel;
                }

                $currentGenericParameter .= $char;
            }

            if (0 !== $nestedLevel) {
                throw new InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
            }

            $genericParameters[] = $currentGenericParameter;

            if (\in_array($genericType, ['array', 'iterable'], true) && 1 === \count($genericParameters)) {
                array_unshift($genericParameters, 'int');
            }

            if ('list' === $genericType && 1 === \count($genericParameters)) {
                $genericType = 'array';
                array_unshift($genericParameters, 'int');
            }

            $type = $genericType;
            $className = null;

            if (class_exists($genericType)) {
                $type = 'object';
                $className = $genericType;
            }

            return self::$cache[$cacheKey] = new self(
                name: $type,
                isNullable: $isNullable,
                className: $className,
                genericParameterTypes: array_map(fn (string $t): self => self::fromString($t), $genericParameters),
            );
        }

        return self::$cache[$cacheKey] = new self($string, isNullable: $isNullable);
    }

    public static function int(bool $nullable = false): self
    {
        return new self('int', isNullable: $nullable);
    }

    public static function float(bool $nullable = false): self
    {
        return new self('float', isNullable: $nullable);
    }

    public static function string(bool $nullable = false): self
    {
        return new self('string', isNullable: $nullable);
    }

    public static function bool(bool $nullable = false): self
    {
        return new self('bool', isNullable: $nullable);
    }

    public static function null(): self
    {
        return new self('null');
    }

    public static function mixed(): self
    {
        return new self('mixed');
    }

    public static function resource(): self
    {
        return new self('resource');
    }

    public static function callable(): self
    {
        return new self('callable');
    }

    public static function array(self $value = null, self $key = null, bool $nullable = false): self
    {
        return self::generic(
            new self('array', isNullable: $nullable),
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function list(self $value = null, bool $nullable = false): self
    {
        return self::array($value, self::int(), $nullable);
    }

    public static function dict(self $value = null, bool $nullable = false): self
    {
        return self::array($value, self::string(), $nullable);
    }

    public static function iterable(self $value = null, self $key = null, bool $nullable = false): self
    {
        return self::generic(
            new self('iterable', isNullable: $nullable),
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function iterableList(self $value = null, bool $nullable = false): self
    {
        return self::iterable($value, self::int(), $nullable);
    }

    public static function iterableDict(self $value = null, bool $nullable = false): self
    {
        return self::iterable($value, self::string(), $nullable);
    }

    public static function object(bool $nullable = false): self
    {
        return new self('object', isNullable: $nullable);
    }

    /**
     * @param class-string $className
     */
    public static function class(string $className, bool $nullable = false): self
    {
        return new self('object', isNullable: $nullable, className: $className);
    }

    public static function enum(string $enumClassName, bool $nullable = false): self
    {
        $reflection = new \ReflectionEnum($enumClassName);

        if ($reflection->isBacked() && null !== ($backingType = $reflection->getBackingType())) {
            return new self('enum', isNullable: $nullable, className: $enumClassName, backingType: new self((string) $backingType));
        }

        return new self('enum', isNullable: $nullable, className: $enumClassName);
    }

    public static function generic(self $main, self ...$parameters): self
    {
        return new self(
            name: $main->name(),
            isNullable: $main->isNullable(),
            className: $main->hasClass() ? $main->className() : null,
            genericParameterTypes: $parameters,
        );
    }

    public static function union(self ...$types): self
    {
        return new self(
            implode('|', array_map(fn (self $t): string => (string) $t, $types)),
            unionTypes: $types,
        );
    }

    public static function intersection(self ...$types): self
    {
        return new self(
            implode('&', array_map(fn (self $t): string => (string) $t, $types)),
            intersectionTypes: $types,
        );
    }

    private function computeStringValue(): string
    {
        if ($this->isUnion()) {
            return implode('|', array_map(fn (self $t): string => (string) $t, $this->unionTypes));
        }

        if ($this->isIntersection()) {
            return implode('&', array_map(fn (self $t): string => (string) $t, $this->intersectionTypes));
        }

        if ($this->isNull()) {
            return 'null';
        }

        $name = $this->hasClass() ? $this->className() : $this->name();

        if ($this->isGeneric()) {
            $name .= sprintf('<%s>', implode(', ', $this->genericParameterTypes));
        }

        return ($this->isNullable() ? '?' : '').$name;
    }
}
