<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Decoder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Config\JsonDeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Decoder\JsonDecoder;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

class JsonDecoderTest extends TestCase
{
    public function testDecode()
    {
        $this->assertSame('foo', JsonDecoder::decode($this->createResource('"foo"'), 0, -1, new DeserializeConfig()));
    }

    public function testDecodeSubset()
    {
        $this->assertSame('bar', JsonDecoder::decode($this->createResource('["foo","bar","baz"]'), 7, 5, new DeserializeConfig()));
    }

    public function testDecodeWithJsonDecodeFlags()
    {
        $config = new DeserializeConfig();

        $this->assertSame(
            1.2345678901234568E+29,
            JsonDecoder::decode($this->createResource('123456789012345678901234567890'), 0, -1, $config),
        );

        $jsonConfig = (new JsonDeserializeConfig())->withFlags(\JSON_BIGINT_AS_STRING);
        $config = $config->withJsonConfig($jsonConfig);

        $this->assertSame(
            '123456789012345678901234567890',
            JsonDecoder::decode($this->createResource('123456789012345678901234567890'), 0, -1, $config),
        );
    }

    public function testDecodeThrowOnInvalidResource()
    {
        $this->expectException(InvalidResourceException::class);

        JsonDecoder::decode(fopen(sprintf('%s/%s', sys_get_temp_dir(), uniqid()), 'w'), 0, -1, new DeserializeConfig());
    }

    public function testDecodeThrowOnInvalidJson()
    {
        $this->expectException(InvalidResourceException::class);

        JsonDecoder::decode($this->createResource('foo"'), 0, -1, new DeserializeConfig());
    }

    /**
     * @return resource
     */
    private function createResource(string $content): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');

        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
