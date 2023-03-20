<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\Template\Template;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Serializer implements SerializerInterface
{
    public function __construct(
        private readonly Template $template,
        private readonly ContainerInterface $runtimeServices,
        private readonly string $templateCacheDir,
    ) {
    }

    public function serialize(mixed $data, string $format, StreamInterface $output = null, SerializeConfig $config = null): string|null
    {
        $shouldOutputString = null === $output;

        $output ??= new MemoryStream();
        $config ??= new SerializeConfig();
        $type = $config->type() ?? Type::fromString(get_debug_type($data));
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

        (require $path)($data, $output->resource(), $config, $this->runtimeServices);

        return $shouldOutputString ? (string) $output : null;
    }
}
