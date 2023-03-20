<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheWarmer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Template\Template as DeserializeTemplate;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\Template\Template as SerializeTemplate;
use Symfony\Component\Serializer\Template\TemplateVariant;
use Symfony\Component\Serializer\Template\TemplateVariation;
use Symfony\Component\Serializer\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * Generates serialization and deserialization templates PHP files.
 *
 * It generates templates for each $formats and each variants
 * of $serializable types limited to $maxVariants.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerializerTemplateCacheWarmer extends CacheWarmer
{
    /**
     * @param list<string> $serializable
     * @param list<string> $formats
     */
    public function __construct(
        private readonly array $serializable,
        private readonly SerializeTemplate $serializeTemplate,
        private readonly DeserializeTemplate $deserializeTemplate,
        private readonly TemplateVariationExtractorInterface $templateVariationExtractor,
        private readonly string $templateCacheDir,
        private readonly array $formats,
        private readonly int $maxVariants,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        foreach ($this->serializable as $s) {
            $type = Type::fromString($s);

            $variations = $this->templateVariationExtractor->extractVariationsFromType($type);
            $variants = $this->variants($variations);

            if (\count($variants) > $this->maxVariants) {
                $this->logger->debug('Too many variants for "{type}", keeping only the first {maxVariants}.', ['type' => $s, 'maxVariants' => $this->maxVariants]);
                $variants = \array_slice($variants, offset: 0, length: $this->maxVariants);
            }

            foreach ($this->formats as $format) {
                $this->warmTemplates($type, $variants, $format);
            }
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param list<array{serialize: TemplateVariant, deserialize: TemplateVariant}> $variants
     */
    private function warmTemplates(Type $type, array $variants, string $format): void
    {
        foreach ($variants as $variant) {
            try {
                $this->writeCacheFile(
                    $this->serializeTemplate->path($type, $format, $variant['serialize']->config),
                    $this->serializeTemplate->content($type, $format, $variant['serialize']->config),
                );
            } catch (ExceptionInterface $e) {
                $this->logger->debug('Cannot generate serialize "{format}" template for "{type}": {exception}', [
                    'format' => $format,
                    'type' => (string) $type,
                    'exception' => $e,
                ]);
            }

            try {
                $this->writeCacheFile(
                    $this->deserializeTemplate->path($type, $format, $variant['deserialize']->config),
                    $this->deserializeTemplate->content($type, $format, $variant['deserialize']->config),
                );
            } catch (ExceptionInterface $e) {
                $this->logger->debug('Cannot generate deserialize "{format}" template for "{type}": {exception}', [
                    'format' => $format,
                    'type' => (string) $type,
                    'exception' => $e,
                ]);
            }
        }
    }

    /**
     * @param list<TemplateVariation> $variations
     *
     * @return list<array{serialize: TemplateVariant, deserialize: TemplateVariant}>
     */
    private function variants(array $variations): array
    {
        $variants = [[]];

        foreach ($variations as $variation) {
            foreach ($variants as $variant) {
                $variants[] = array_merge([$variation], $variant);
            }
        }

        return array_map(fn (array $variations): array => [
            'serialize' => new TemplateVariant(new SerializeConfig(), $variations),
            'deserialize' => new TemplateVariant(new DeserializeConfig(), $variations),
        ], $variants);
    }
}
