<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Stream;

use Symfony\Component\Serializer\Exception\InvalidResourceException;
use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $resource;

    public function __construct(
        protected readonly string $filename,
        protected readonly string $mode,
        string $content = null,
    ) {
        if (false === $resource = @fopen($this->filename, $this->mode)) {
            throw new RuntimeException(sprintf('Cannot open "%s" resource', $this->filename));
        }

        $this->resource = $resource;

        if (null === $content) {
            return;
        }

        if (false === @fwrite($this->resource, $content)) {
            throw new InvalidResourceException($this->resource);
        }

        if (false === @rewind($this->resource)) {
            throw new InvalidResourceException($this->resource);
        }
    }

    final public function resource(): mixed
    {
        return $this->resource;
    }

    final public function __toString(): string
    {
        if (false === @rewind($this->resource)) {
            throw new InvalidResourceException($this->resource);
        }

        if (false === $content = @stream_get_contents($this->resource)) {
            throw new InvalidResourceException($this->resource);
        }

        return $content;
    }
}
