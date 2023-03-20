<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Stream;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Stream\Stream;

class StreamTest extends TestCase
{
    public function testCreateStream()
    {
        $stream = new class() extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        $streamMetadata = stream_get_meta_data($stream->resource());

        $this->assertSame('php://memory', $streamMetadata['uri']);
        $this->assertSame('w+b', $streamMetadata['mode']);
    }

    public function testCreateWithString()
    {
        $stream = new class('content') extends Stream {
            public function __construct(string $content)
            {
                parent::__construct('php://memory', 'w+b', $content);
            }
        };

        $this->assertSame('content', stream_get_contents($stream->resource()));
    }

    public function testToString()
    {
        $stream = new class() extends Stream {
            public function __construct()
            {
                parent::__construct('php://memory', 'w+b');
            }
        };

        fwrite($stream->resource(), 'content');

        $this->assertSame('content', (string) $stream);
    }
}
