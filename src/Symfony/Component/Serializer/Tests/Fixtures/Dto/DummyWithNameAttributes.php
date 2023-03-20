<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

class DummyWithNameAttributes
{
    #[SerializedName('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
