<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Serializer\Type\Type;

class PropertyMetadataTest extends TestCase
{
    public function testThrowOnNonStaticFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        new PropertyMetadata('useless', Type::mixed(), [(new DummyWithMethods())->nonStatic(...)]);
    }

    public function testThrowOnNonAnonymousFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        new PropertyMetadata('useless', Type::mixed(), [fn () => 'useless']);
    }
}
