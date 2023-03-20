<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\DependencyInjection\SerializablePass;
use Symfony\Component\Serializer\Tests\Fixtures\Annotations\AbstractDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;

class SerializablePassTest extends TestCase
{
    public function testFindSerializableClasses()
    {
        $container = new ContainerBuilder();
        $container->setParameter('serializer.serializable_paths', [\dirname(__DIR__, 1).'/Fixtures/{Dto,Invalid}']);
        $container->register('serializer.serializer');

        (new SerializablePass())->process($container);

        $serializable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('serializer.serializable')) {
                continue;
            }

            $serializable[] = $definition->getClass();
        }

        $this->assertContains(ClassicDummy::class, $serializable);
        $this->assertNotContains(AbstractDummy::class, $serializable);
    }
}
