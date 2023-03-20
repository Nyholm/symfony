<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerLazyGhostCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Serializer\Serialize\SerializerInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;

class SerializerLazyGhostCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!interface_exists(SerializerInterface::class)) {
            $this->markTestSkipped('experimental version of symfony/serializer is required');
        }

        $this->cacheDir = sprintf('%s/symfony_serializer_lazy_ghost', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUpLazyGhost()
    {
        (new SerializerLazyGhostCacheWarmer([ClassicDummy::class, 'int'], $this->cacheDir))->warmUp('useless');

        $this->assertSame(
            array_map(fn (string $c): string => sprintf('%s/%s.php', $this->cacheDir, hash('xxh128', $c)), [ClassicDummy::class]),
            glob($this->cacheDir.'/*'),
        );
    }
}
