<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Config;

/**
 * Deserialization base configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class DeserializeConfig
{
    /**
     * @var list<string>
     */
    protected array $groups = [];

    protected bool $forceGenerateTemplate = false;

    protected ?bool $lazy = null;

    protected string $dateTimeFormat = \DateTimeInterface::RFC3339;

    protected JsonDeserializeConfig $jsonConfig;

    protected CsvDeserializeConfig $csvConfig;

    public function __construct()
    {
        $this->jsonConfig = new JsonDeserializeConfig();
        $this->csvConfig = new CsvDeserializeConfig();
    }

    /**
     * @return list<string>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * @param non-empty-string|non-empty-array<int, non-empty-string> $groups
     */
    public function withGroups(array|string $groups): static
    {
        $clone = clone $this;
        $clone->groups = array_values(array_unique((array) $groups));

        return $clone;
    }

    public function forceGenerateTemplate(): bool
    {
        return $this->forceGenerateTemplate;
    }

    public function withForceGenerateTemplate(bool $forceGenerateTemplate = true): static
    {
        $clone = clone $this;
        $clone->forceGenerateTemplate = $forceGenerateTemplate;

        return $clone;
    }

    public function lazy(): ?bool
    {
        return $this->lazy;
    }

    public function withLazy(bool $lazy = true): static
    {
        $clone = clone $this;
        $clone->lazy = $lazy;

        return $clone;
    }

    public function dateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function withDateTimeFormat(string $dateTimeFormat): static
    {
        $clone = clone $this;
        $clone->dateTimeFormat = $dateTimeFormat;

        return $clone;
    }

    public function json(): JsonDeserializeConfig
    {
        return $this->jsonConfig;
    }

    public function withJsonConfig(JsonDeserializeConfig $config): static
    {
        $clone = clone $this;
        $clone->jsonConfig = $config;

        return $clone;
    }

    public function csv(): CsvDeserializeConfig
    {
        return $this->csvConfig;
    }

    public function withCsvConfig(CsvDeserializeConfig $config): static
    {
        $clone = clone $this;
        $clone->csvConfig = $config;

        return $clone;
    }
}
