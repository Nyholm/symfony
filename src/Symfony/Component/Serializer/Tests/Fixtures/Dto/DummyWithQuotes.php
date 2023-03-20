<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

class DummyWithQuotes
{
    #[SerializedName('"name"')]
    public string $name = '"quoted" dummy';
}
