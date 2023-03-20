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
use Symfony\Component\Serializer\Template\TemplateVariation;

class TemplateVariationTest extends TestCase
{
    public function testCompare()
    {
        $aa = new TestTemplateVariation(type: 'a', value: 'a');
        $ab = new TestTemplateVariation(type: 'a', value: 'b');
        $ba = new TestTemplateVariation(type: 'b', value: 'a');

        $this->assertSame(0, $aa->compare($aa));
        $this->assertSame(-1, $aa->compare($ab));
        $this->assertSame(-1, $aa->compare($ba));
        $this->assertSame(-1, $ab->compare($ba));
        $this->assertSame(1, $ba->compare($ab));
    }

    public function testToString()
    {
        $this->assertSame('type-value', (string) (new TestTemplateVariation(type: 'type', value: 'value')));
    }
}

readonly class TestTemplateVariation extends TemplateVariation
{
    public function __construct(string $type, string $value)
    {
        parent::__construct($type, $value);
    }

    public function configure(SerializeConfig|DeserializeConfig $config): SerializeConfig
    {
        return $config;
    }
}
