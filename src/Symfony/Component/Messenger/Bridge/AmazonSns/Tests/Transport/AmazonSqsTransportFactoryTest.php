<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Bridge\AmazonSns\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\AmazonSns\Transport\AmazonSnsTransportFactory;

class AmazonSnsTransportFactoryTest extends TestCase
{
    public function testSupportsOnlySnsTransports()
    {
        $factory = new AmazonSnsTransportFactory();

        $this->assertTrue($factory->supports('sns://localhost', []));
        $this->assertTrue($factory->supports('https://sns.us-east-2.amazonaws.com/123456789012/ab1-MyQueue-A2BCDEF3GHI4', []));
        $this->assertFalse($factory->supports('redis://localhost', []));
        $this->assertFalse($factory->supports('invalid-dsn', []));
    }
}
