<?php

namespace Test\Resources;

class Cat
{
    public function __construct(private string $name = '', private int $age = 5)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }
}
