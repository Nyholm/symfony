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

use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TypeNameResolver
{
    /**
     * @param class-string                $className
     * @param array<string, class-string> $uses
     * @param list<string>                $templateNames
     */
    public function __construct(
        private readonly string $className,
        private readonly string $namespace,
        private readonly array $uses,
        private readonly array $templateNames,
    ) {
    }

    /**
     * @param \ReflectionClass<object> $class
     * @param list<string>             $templateNames
     */
    public static function createForClass(\ReflectionClass $class, array $templateNames): self
    {
        $context = (new ContextFactory())->createFromReflector($class);
        $namespace = $context->getNamespace();

        /** @var array<string, class-string> $uses */
        $uses = $context->getNamespaceAliases();

        /** @var class-string $className */
        $className = str_replace($namespace.'\\', '', $class->getName());

        return new self($className, $namespace, $uses, $templateNames);
    }

    /**
     * @return class-string
     */
    public function resolveRootClass(): string
    {
        return $this->resolve($this->className);
    }

    /**
     * @return class-string
     */
    public function resolveParentClass(): string
    {
        $rootClassName = $this->resolveRootClass();

        if (false === $parentClass = (new \ReflectionClass($rootClassName))->getParentClass()) {
            throw new LogicException(sprintf('"%s" class do not extend any class.', $rootClassName));
        }

        /** @var class-string $parentClassName */
        $parentClassName = str_replace($this->namespace.'\\', '', $parentClass->getName());

        return $this->resolve($parentClassName);
    }

    /**
     * @template T of string|class-string
     *
     * @param T $name
     *
     * @return T
     */
    public function resolve(string $name): string
    {
        if (\in_array($name, $this->templateNames)) {
            return $name;
        }

        if (str_starts_with($name, '\\')) {
            /** @var T $name */
            $name = ltrim($name, '\\');

            return $name;
        }

        $nameParts = explode('\\', $name);
        $usedPart = $nameParts[0];

        if (!isset($this->uses[$usedPart])) {
            /** @var T $name */
            $name = sprintf('%s\\%s', $this->namespace, $name);

            return $name;
        }

        if (1 === \count($nameParts)) {
            /** @var T $name */
            $name = $this->uses[$usedPart];

            return $name;
        }

        array_shift($nameParts);

        /** @var T $name */
        $name = sprintf('%s\\%s', $this->uses[$usedPart], implode('\\', $nameParts));

        return $name;
    }
}
