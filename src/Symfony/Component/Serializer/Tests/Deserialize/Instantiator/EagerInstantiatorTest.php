<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Instantiator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Instantiator\EagerInstantiator;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;

class EagerInstantiatorTest extends TestCase
{
    public function testInstantiate()
    {
        $expected = new ClassicDummy();
        $expected->id = 100;
        $expected->name = 'dummy';

        $properties = [
            'id' => fn () => 100,
            'name' => fn () => 'dummy',
        ];

        $this->assertEquals($expected, (new EagerInstantiator())->instantiate(ClassicDummy::class, $properties));
    }

    public function testThrowOnInvalidProperty()
    {
        $this->expectException(UnexpectedValueException::class);

        (new EagerInstantiator())->instantiate(ClassicDummy::class, [
            'id' => fn () => ['an', 'array'],
        ]);
    }
}
