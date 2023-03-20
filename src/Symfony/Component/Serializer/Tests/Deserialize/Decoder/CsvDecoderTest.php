<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Internal\Deserialize\Csv;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig;
use Symfony\Component\Serializer\Deserialize\Decoder\CsvDecoder;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

class CsvDecoderTest extends TestCase
{
    public function testDecode()
    {
        $this->assertSame([['foo']], CsvDecoder::decode($this->createResource("0\nfoo"), 0, -1, new DeserializeConfig()));
    }

    public function testDecodeSubset()
    {
        $this->assertSame([['foo']], CsvDecoder::decode($this->createResource("before0\nfooafter"), 6, 5, new DeserializeConfig()));
    }

    public function testDecodeThrowOnInvalidResource()
    {
        $this->expectException(InvalidResourceException::class);

        (new CsvDecoder())->decode(fopen(sprintf('%s/%s', sys_get_temp_dir(), uniqid()), 'w'), 0, -1, new DeserializeConfig());
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
