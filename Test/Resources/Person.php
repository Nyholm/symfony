<?php

namespace Test\Resources;

class Person
{
    private string $name;
    public int $age;
    public null|Cat $cat = null;
    public null|Cat $cat2 = null;

    public function __construct($name = '', int $age = 5, Cat $cat = null)
    {
        $this->name = $name;
        $this->age = $age;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getCat(): ?Cat
    {
        return $this->cat;
    }

    public function setCat(?Cat $cat): void
    {
        $this->cat = $cat;
    }

    public function getCat2(): ?Cat
    {
        return $this->cat2;
    }

    public function setCat2(?Cat $cat): void
    {
        $this->cat2 = $cat;
    }

    public function getType(): string
    {
        return 'person';
    }

    public function getRandom(): mixed
    {
        return 'person';
    }
}
