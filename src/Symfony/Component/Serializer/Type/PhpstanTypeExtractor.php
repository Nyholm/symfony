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

use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Extracts type from PHPStan documentation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PhpstanTypeExtractor implements TypeExtractorInterface, TemplateExtractorInterface
{
    private readonly PhpstanTypeHelper $phpstanTypeHelper;
    private readonly PhpDocParser $phpstanDocParser;
    private readonly Lexer $phpstanLexer;

    public function __construct(
        private readonly TypeExtractorInterface $decoratedTypeExtractor,
    ) {
        $this->phpstanTypeHelper = new PhpstanTypeHelper();
        $this->phpstanDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $this->phpstanLexer = new Lexer();
    }

    public function extractTypeFromProperty(\ReflectionProperty $property): Type
    {
        if (null === $typeNode = $this->getTypeNode($property)) {
            return $this->decoratedTypeExtractor->extractTypeFromProperty($property);
        }

        return $this->phpstanTypeHelper->getType($typeNode, $property->getDeclaringClass(), $this->getTemplateNodes($property->getDeclaringClass()));
    }

    public function extractTypeFromFunctionReturn(\ReflectionFunctionAbstract $function): Type
    {
        if (null === $typeNode = $this->getTypeNode($function)) {
            return $this->decoratedTypeExtractor->extractTypeFromFunctionReturn($function);
        }

        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();
        if (null === $declaringClass) {
            throw new UnexpectedValueException(sprintf('"%s()" does not have any declaring class.', $function->getName()));
        }

        return $this->phpstanTypeHelper->getType($typeNode, $declaringClass, $this->getTemplateNodes($declaringClass));
    }

    public function extractTypeFromParameter(\ReflectionParameter $parameter): Type
    {
        if (null === $typeNode = $this->getTypeNode($parameter)) {
            return $this->decoratedTypeExtractor->extractTypeFromParameter($parameter);
        }

        $function = $parameter->getDeclaringFunction();
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $declaringClass) {
            throw new UnexpectedValueException(sprintf('"%s()" does not have any declaring class.', $function->getName()));
        }

        return $this->phpstanTypeHelper->getType($typeNode, $declaringClass, $this->getTemplateNodes($declaringClass));
    }

    public function extractTemplateFromClass(\ReflectionClass $class): array
    {
        $templates = array_map(fn (TemplateTagValueNode $t): string => $t->name, $this->getTemplateNodes($class));

        if (array_unique($templates) !== $templates) {
            throw new UnexpectedValueException(sprintf('Templates defined in "%s" must be unique.', $class->getName()));
        }

        return $templates;
    }

    private function getTypeNode(\ReflectionProperty|\ReflectionFunctionAbstract|\ReflectionParameter $reflection): ?TypeNode
    {
        $rawDocNode = $reflection instanceof \ReflectionParameter ? $reflection->getDeclaringFunction()->getDocComment() : $reflection->getDocComment();
        if (!$rawDocNode) {
            return null;
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tagName = match (true) {
            $reflection instanceof \ReflectionProperty => '@var',
            $reflection instanceof \ReflectionFunctionAbstract => '@return',
            $reflection instanceof \ReflectionParameter => '@param',
        };

        $tags = $docNode->getTagsByName($tagName);
        $tag = reset($tags) ?: null;

        /** @var VarTagValueNode|ReturnTagValueNode|ParamTagValueNode|InvalidTagValueNode|null $tagValue */
        $tagValue = $tag?->value;

        if (null === $tagValue || $tagValue instanceof InvalidTagValueNode) {
            return null;
        }

        return $tagValue->type;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<TemplateTagValueNode>
     */
    private function getTemplateNodes(\ReflectionClass $reflection): array
    {
        if (null === $rawDocNode = $reflection->getDocComment() ?: null) {
            return [];
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tags = $docNode->getTagsByName('@template');

        return array_values(array_filter(
            array_map(fn (PhpDocTagNode $t): PhpDocTagValueNode => $t->value, $tags),
            fn (PhpDocTagValueNode $v): bool => $v instanceof TemplateTagValueNode,
        ));
    }
}
