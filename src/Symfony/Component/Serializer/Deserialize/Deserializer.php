<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Deserialize\Template\Template;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Deserializer implements DeserializerInterface
{
    public function __construct(
        private readonly Template $template,
        private readonly ContainerInterface $runtimeServices,
        private readonly InstantiatorInterface $instantiator,
        private readonly string $templateCacheDir,
    ) {
    }

    public function deserialize(StreamInterface|string $input, Type $type, string $format, DeserializeConfig $config = null): mixed
    {
        if (\is_string($input)) {
            $input = new MemoryStream($input);
        }

        $config ??= new DeserializeConfig();
        $path = $this->template->path($type, $format, $config);

        if (!file_exists($path) || $config->forceGenerateTemplate()) {
            $content = $this->template->content($type, $format, $config);

            if (!file_exists($this->templateCacheDir)) {
                mkdir($this->templateCacheDir, recursive: true);
            }

            $tmpFile = @tempnam(\dirname($path), basename($path));
            if (false === @file_put_contents($tmpFile, $content)) {
                throw new RuntimeException(sprintf('Failed to write "%s" template file.', $path));
            }

            @rename($tmpFile, $path);
            @chmod($path, 0666 & ~umask());
        }

        return (require $path)($input->resource(), $config, $this->instantiator, $this->runtimeServices);
    }
}
