<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Template\GroupTemplateVariation;

class GroupTemplateVariationTest extends TestCase
{
    public function testConfigure()
    {
        $serializeConfig = new SerializeConfig();
        $deserializeConfig = new DeserializeConfig();

        $groupOne = new GroupTemplateVariation('groupOne');
        $groupTwo = new GroupTemplateVariation('groupTwo');

        $serializeConfig = $groupOne->configure($serializeConfig);
        $deserializeConfig = $groupOne->configure($deserializeConfig);

        $this->assertSame(['groupOne'], $serializeConfig->groups());
        $this->assertSame(['groupOne'], $deserializeConfig->groups());

        $serializeConfig = $groupTwo->configure($serializeConfig);
        $deserializeConfig = $groupTwo->configure($deserializeConfig);

        $this->assertSame(['groupOne', 'groupTwo'], $serializeConfig->groups());
        $this->assertSame(['groupOne', 'groupTwo'], $deserializeConfig->groups());

        $serializeConfig = $groupOne->configure($serializeConfig);
        $deserializeConfig = $groupOne->configure($deserializeConfig);

        $this->assertSame(['groupOne', 'groupTwo'], $serializeConfig->groups());
        $this->assertSame(['groupOne', 'groupTwo'], $deserializeConfig->groups());
    }
}
