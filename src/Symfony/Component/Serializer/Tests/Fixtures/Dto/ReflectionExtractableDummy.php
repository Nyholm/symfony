<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class ReflectionExtractableDummy extends AbstractDummy
{
    public mixed $mixed;

    public int $int;

    public string $string;

    public float $float;

    public bool $bool;

    public array $array;

    public self $self;

    public parent $parent;

    public ClassicDummy $class;

    public string|int $union;

    public \Stringable&\Countable $intersection;

    public ?int $nullableBuiltin;
    public ?ClassicDummy $nullableClass;
    public string|int|null $nullableUnion;

    public $undefined;

    public function mixed(mixed $_): mixed
    {
        return $this->mixed;
    }

    public function int(int $_): int
    {
        return $this->int;
    }

    public function string(string $_): string
    {
        return $this->string;
    }

    public function float(float $_): float
    {
        return $this->float;
    }

    public function bool(bool $_): bool
    {
        return $this->bool;
    }

    public function array(array $_): array
    {
        return $this->array;
    }

    public function self(self $_): self
    {
        return $this->self;
    }

    public function parent(parent $_): parent
    {
        return $this->parent;
    }

    public function class(ClassicDummy $_): ClassicDummy
    {
        return $this->class;
    }

    public function union(string|int $_): string|int
    {
        return $this->union;
    }

    public function intersection(\Stringable&\Countable $_): \Stringable&\Countable
    {
        return $this->intersection;
    }

    public function nullableBuiltin(?int $_): ?int
    {
        return $this->nullableBuiltin;
    }

    public function nullableClass(?ClassicDummy $_): ?ClassicDummy
    {
        return $this->nullableClass;
    }

    public function nullableUnion(string|int|null $_): string|int|null
    {
        return $this->nullableUnion;
    }

    public function void(): void
    {
    }

    public function never(): never
    {
        exit;
    }

    public function undefined($_)
    {
    }
}
