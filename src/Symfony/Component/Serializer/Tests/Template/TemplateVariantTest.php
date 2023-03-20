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
use Symfony\Component\Serializer\Template\TemplateVariant;
use Symfony\Component\Serializer\Template\TemplateVariation;

class TemplateVariantTest extends TestCase
{
    public function testCreateSortVariations()
    {
        $variant = new TemplateVariant(new SerializeConfig(), [
            new GroupTemplateVariation('a'),
            new GroupTemplateVariation('c'),
            new GroupTemplateVariation('b'),
        ]);

        $this->assertSame(['a', 'b', 'c'], array_map(fn (TemplateVariation $v) => $v->value, $variant->variations));
    }

    public function testCreateConfigureConfig()
    {
        $variant = new TemplateVariant(new SerializeConfig(), [
            new GroupTemplateVariation('a'),
            new GroupTemplateVariation('c'),
            new GroupTemplateVariation('b'),
        ]);

        $this->assertSame(['a', 'b', 'c'], $variant->config->groups());

        $variant = new TemplateVariant(new DeserializeConfig(), [
            new GroupTemplateVariation('a'),
            new GroupTemplateVariation('c'),
            new GroupTemplateVariation('b'),
        ]);

        $this->assertSame(['a', 'b', 'c'], $variant->config->groups());
    }
}
