<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;
use Symfony\Component\Serializer\Php\ClosureNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ParametersNode;
use Symfony\Component\Serializer\Php\PhpDocNode;
use Symfony\Component\Serializer\Php\ReturnNode;
use Symfony\Component\Serializer\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelBuilderInterface;
use Symfony\Component\Serializer\Template\TemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * Provide path and contents of a serialization template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Template
{
    /**
     * @param array<string, TemplateGeneratorInterface> $generators
     */
    public function __construct(
        private readonly TemplateVariationExtractorInterface $variationExtractor,
        private readonly DataModelBuilderInterface $dataModelBuilder,
        private readonly array $generators,
        private readonly string $cacheDir,
    ) {
    }

    public function path(Type $type, string $format, SerializeConfig $config): string
    {
        $hash = hash('xxh128', (string) $type);

        $variations = $this->variationExtractor->extractVariationsFromConfig($config);
        if ([] !== $variations) {
            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variations)));
        }

        return sprintf('%s%s%s.serialize.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, $hash, $format);
    }

    public function content(Type $type, string $format, SerializeConfig $config): string
    {
        $generator = $this->generators[$format] ?? null;
        if (null === $generator) {
            throw new UnsupportedFormatException(sprintf('"%s" format is not supported.', $format));
        }

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([
            sprintf('@param %s $data', $type),
            '@param resource $resource',
        ]));
        $phpDoc = $compiler->source();

        $compiler->reset();

        $argumentsNode = new ParametersNode([
            'data' => 'mixed',
            'resource' => 'mixed',
            'config' => '\\'.SerializeConfig::class,
            'services' => '\\'.ContainerInterface::class,
        ]);

        $compiler->indent();
        $bodyNodes = $generator->generate($this->dataModelBuilder->build($type, new VariableNode('data'), $config), $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
