<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Generates lazy ghost {@see Symfony\Component\VarExporter\LazyGhostTrait}
 * PHP files for $serializable types.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerializerLazyGhostCacheWarmer extends CacheWarmer
{
    /**
     * @param list<string> $serializable
     */
    public function __construct(
        private readonly array $serializable,
        private readonly string $lazyGhostCacheDir,
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->lazyGhostCacheDir)) {
            mkdir($this->lazyGhostCacheDir, recursive: true);
        }

        foreach ($this->serializable as $s) {
            $type = Type::fromString($s);

            if (!$type->isObject() || !$type->hasClass()) {
                continue;
            }

            $this->warmClassLazyGhost($type->className());
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param class-string $className
     */
    private function warmClassLazyGhost(string $className): void
    {
        $path = sprintf('%s%s%s.php', $this->lazyGhostCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className));

        $this->writeCacheFile($path, sprintf(
            'class %s%s',
            sprintf('%sGhost', preg_replace('/\\\\/', '', $className)),
            ProxyHelper::generateLazyGhost(new \ReflectionClass($className)),
        ));
    }
}
