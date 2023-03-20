<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class DummyWithGroups
{
    public string $none = 'none';

    #[Groups('one')]
    public string $one = 'one';

    #[Groups(['one', 'two'])]
    public string $oneAndTwo = 'oneAndTwo';

    #[Groups(['two', 'three'])]
    public string $twoAndThree = 'twoAndThree';
}
