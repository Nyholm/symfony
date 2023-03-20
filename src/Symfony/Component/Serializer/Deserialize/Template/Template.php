<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Template;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\DataModel\DataModelBuilderInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;
use Symfony\Component\Serializer\Php\ClosureNode;
use Symfony\Component\Serializer\Php\Compiler;
use Symfony\Component\Serializer\Php\ExpressionNode;
use Symfony\Component\Serializer\Php\ParametersNode;
use Symfony\Component\Serializer\Php\PhpDocNode;
use Symfony\Component\Serializer\Php\ReturnNode;
use Symfony\Component\Serializer\Template\TemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * Provide path and contents of a deserialization template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Template
{
    /**
     * @param array<string, array<string, TemplateGeneratorInterface>> $generators
     */
    public function __construct(
        private readonly TemplateVariationExtractorInterface $variationExtractor,
        private readonly DataModelBuilderInterface $dataModelBuilder,
        private readonly array $generators,
        private readonly string $cacheDir,
        private readonly bool $defaultLazy,
    ) {
    }

    public function path(Type $type, string $format, DeserializeConfig $config): string
    {
        $hash = hash('xxh128', (string) $type);

        $variations = $this->variationExtractor->extractVariationsFromConfig($config);
        if ([] !== $variations) {
            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variations)));
        }

        return sprintf(
            '%s%s%s.deserialize.%s.%s.php',
            $this->cacheDir,
            \DIRECTORY_SEPARATOR,
            $hash,
            ($config->lazy() ?? $this->defaultLazy) ? 'lazy' : 'eager',
            $format,
        );
    }

    public function content(Type $type, string $format, DeserializeConfig $config): string
    {
        $lazy = $config->lazy() ?? $this->defaultLazy;

        $generator = $this->generators[$format][$lazy ? 'lazy' : 'eager'] ?? null;
        if (null === $generator) {
            throw new UnsupportedFormatException(sprintf('"%s" format is not supported %s.', $format, $lazy ? 'lazily' : 'eagerly'));
        }

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([
            '@param resource $resource',
            sprintf('@return %s', $type),
        ]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $parametersNode = new ParametersNode([
            'resource' => 'mixed',
            'config' => '\\'.DeserializeConfig::class,
            'instantiator' => '\\'.InstantiatorInterface::class,
            'services' => '\\'.ContainerInterface::class,
        ]);

        $compiler->indent();
        $bodyNodes = $generator->generate($this->dataModelBuilder->build($type, $config), $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($parametersNode, 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
