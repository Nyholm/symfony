<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Config;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * CSV format serialization/deserialization common configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract class CsvConfig
{
    protected string $delimiter = ',';

    protected string $enclosure = '"';

    protected string $escapeChar = '';

    protected string $keySeparator = '.';

    /**
     * @var list<mixed>
     */
    protected array $headers = [];

    protected bool $escapedFormulas = false;

    protected bool $asCollection = true;

    protected bool $noHeaders = false;

    protected string $endOfLine = "\n";

    protected bool $utf8Bom = false;

    /**
     * The column delimiter character.
     */
    public function delimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withDelimiter(string $delimiter): static
    {
        if (1 !== \strlen($delimiter)) {
            throw new InvalidArgumentException(sprintf('The "%s" delimiter must be a single character.', $delimiter));
        }

        $clone = clone $this;
        $clone->delimiter = $delimiter;

        return $clone;
    }

    /**
     * The field enclosure character.
     */
    public function enclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withEnclosure(string $enclosure): static
    {
        if (1 !== \strlen($enclosure)) {
            throw new InvalidArgumentException(sprintf('The "%s" enclosure must be a single character.', $enclosure));
        }

        $clone = clone $this;
        $clone->enclosure = $enclosure;

        return $clone;
    }

    /**
     * The escape character.
     */
    public function escapeChar(): string
    {
        return $this->escapeChar;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function withEscapeChar(string $escapeChar): static
    {
        if (\strlen($escapeChar) > 1) {
            throw new InvalidArgumentException(sprintf('The "%s" escape character must be empty or a single character.', $escapeChar));
        }

        $clone = clone $this;
        $clone->escapeChar = $escapeChar;

        return $clone;
    }

    /**
     * The key separator when flattening arrays.
     */
    public function keySeparator(): string
    {
        return $this->keySeparator;
    }

    public function withKeySeparator(string $keySeparator): static
    {
        $clone = clone $this;
        $clone->keySeparator = $keySeparator;

        return $clone;
    }

    /**
     * The CSV headers.
     *
     * @return list<mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @param list<mixed> $headers
     */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        $clone->headers = $headers;

        return $clone;
    }

    /**
     * Whether formulas should be escaped.
     */
    public function escapedFormulas(): bool
    {
        return $this->escapedFormulas;
    }

    public function withEscapedFormulas(bool $escapedFormulas): static
    {
        $clone = clone $this;
        $clone->escapedFormulas = $escapedFormulas;

        return $clone;
    }

    /**
     * Whether the decoded result should be considered as a collection or as a single element.
     */
    public function asCollection(): bool
    {
        return $this->asCollection;
    }

    public function withAsCollection(bool $asCollection): static
    {
        $clone = clone $this;
        $clone->asCollection = $asCollection;

        return $clone;
    }

    /**
     * Whether the input (or output) is containing (or will contain) headers.
     */
    public function noHeaders(): bool
    {
        return $this->noHeaders;
    }

    public function withNoHeaders(bool $noHeaders): static
    {
        $clone = clone $this;
        $clone->noHeaders = $noHeaders;

        return $clone;
    }

    /**
     * The end of line characters.
     */
    public function endOfLine(): string
    {
        return $this->endOfLine;
    }

    public function withEndOfLine(string $endOfLine): static
    {
        $clone = clone $this;
        $clone->endOfLine = $endOfLine;

        return $clone;
    }

    /**
     * Whether to add the UTF-8 Byte Order Mark (BOM) at the beginning of the encoded result or not.
     */
    public function utf8Bom(): bool
    {
        return $this->utf8Bom;
    }

    public function withOutputUtf8Bom(bool $utf8Bom): static
    {
        $clone = clone $this;
        $clone->utf8Bom = $utf8Bom;

        return $clone;
    }
}
