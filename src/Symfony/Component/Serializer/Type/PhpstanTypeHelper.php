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

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PhpstanTypeHelper
{
    /**
     * @param \ReflectionClass<object>   $declaringClass
     * @param list<TemplateTagValueNode> $templateNodes
     */
    public function getType(TypeNode $typeNode, \ReflectionClass $declaringClass, array $templateNodes): Type
    {
        $templateNodeNames = array_map(fn (TemplateTagValueNode $t): string => $t->name, $templateNodes);

        return Type::fromString($this->extractType($typeNode, TypeNameResolver::createForClass($declaringClass, $templateNodeNames)));
    }

    private function extractType(TypeNode $node, TypeNameResolver $nameResolver): string
    {
        if ($node instanceof UnionTypeNode) {
            return implode('|', array_map(fn (TypeNode $t): string => $this->extractType($t, $nameResolver), $node->types));
        }

        if ($node instanceof IntersectionTypeNode) {
            return implode('&', array_map(fn (TypeNode $t): string => $this->extractType($t, $nameResolver), $node->types));
        }

        if ($node instanceof NullableTypeNode) {
            return sprintf('?%s', $this->extractType($node->type, $nameResolver));
        }

        if ($node instanceof IdentifierTypeNode) {
            return $this->extractIdentifierType($node, $nameResolver);
        }

        if ($node instanceof GenericTypeNode) {
            return $this->extractGenericType($node, $nameResolver);
        }

        if ($node instanceof ArrayTypeNode || $node instanceof ArrayShapeNode) {
            return $this->extractArrayType($node, $nameResolver);
        }

        if ($node instanceof ThisTypeNode) {
            return $nameResolver->resolveRootClass();
        }

        if ($node instanceof CallableTypeNode) {
            return 'callable';
        }

        throw new UnsupportedException(sprintf('"%s" type is not supported.', (string) $node));
    }

    private function extractIdentifierType(IdentifierTypeNode $node, TypeNameResolver $nameResolver): string
    {
        return match ($node->name) {
            'bool', 'boolean', 'true', 'false' => 'bool',
            'int', 'integer' => 'int',
            'float' => 'float',
            'string' => 'string',
            'resource' => 'resource',
            'object' => 'object',
            'callable' => 'callable',
            'array', 'non-empty-array' => 'array',
            'list', 'non-empty-list' => 'list',
            'iterable' => 'iterable',
            'mixed' => 'mixed',
            'null' => 'null',
            'static', 'self' => $nameResolver->resolveRootClass(),
            'parent' => $nameResolver->resolveParentClass(),
            default => $nameResolver->resolve($node->name),
        };
    }

    private function extractGenericType(GenericTypeNode $node, TypeNameResolver $nameResolver): string
    {
        $genericParameters = array_map(fn (TypeNode $t): string => $this->extractType($t, $nameResolver), $node->genericTypes);

        if ('array' === $mainType = $this->extractType($node->type, $nameResolver)) {
            $keyType = 'int';
            $valueType = $genericParameters[0];
            if (2 === \count($genericParameters)) {
                $keyType = $valueType;
                $valueType = $genericParameters[1];
            }

            $genericParameters = [$keyType, $valueType];
        }

        return sprintf('%s<%s>', $mainType, implode(', ', $genericParameters));
    }

    private function extractArrayType(ArrayTypeNode|ArrayShapeNode $node, TypeNameResolver $nameResolver): string
    {
        if ($node instanceof ArrayTypeNode) {
            return sprintf('array<int, %s>', $this->extractType($node->type, $nameResolver));
        }

        if ([] === $items = $node->items) {
            return 'array<string, mixed>';
        }

        $valueType = $node->items[0]->valueType;

        if (\count($items) > 1) {
            $valueType = new UnionTypeNode(array_map(fn (ArrayShapeItemNode $i): TypeNode => $i->valueType, $node->items));
        }

        return sprintf('array<string, %s>', $this->extractType($valueType, $nameResolver));
    }
}
