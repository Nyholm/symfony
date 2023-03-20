<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Config;

use Symfony\Component\Serializer\Type\Type;

/**
 * Serialization base configuration.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class SerializeConfig
{
    protected ?Type $type = null;

    /**
     * @var list<string>
     */
    protected array $groups = [];

    protected bool $forceGenerateTemplate = false;

    protected int $maxDepth = 32;

    protected string $dateTimeFormat = \DateTimeInterface::RFC3339;

    protected JsonSerializeConfig $jsonConfig;

    protected CsvSerializeConfig $csvConfig;

    public function __construct()
    {
        $this->jsonConfig = new JsonSerializeConfig();
        $this->csvConfig = new CsvSerializeConfig();
    }

    public function type(): ?Type
    {
        return $this->type;
    }

    public function withType(Type $type): static
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    /**
     * @return list<non-empty-string>
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

    /**
     * @return positive-int
     */
    public function maxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * @param positive-int $maxDepth
     */
    public function withMaxDepth(int $maxDepth): static
    {
        $clone = clone $this;
        $clone->maxDepth = $maxDepth;

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

    public function json(): JsonSerializeConfig
    {
        return $this->jsonConfig;
    }

    public function withJsonConfig(JsonSerializeConfig $config): static
    {
        $clone = clone $this;
        $clone->jsonConfig = $config;

        return $clone;
    }

    public function csv(): CsvSerializeConfig
    {
        return $this->csvConfig;
    }

    public function withCsvConfig(CsvSerializeConfig $config): static
    {
        $clone = clone $this;
        $clone->csvConfig = $config;

        return $clone;
    }
}
