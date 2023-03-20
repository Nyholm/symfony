<?php

/**
 * @param resource $resource
 * @return ?array<int, mixed>
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?array<int, mixed>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): ?array {
        $boundaries = "\\SPLITTER"::splitList($resource, $offset, $length);
        if (null === $boundaries) {
            return null;
        }
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, &$providers): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => ($providers["mixed"])($resource, $b[0], $b[1]);
            }
        };
        return \iterator_to_array(($iterable)($resource, $boundaries));
    };
    $providers["mixed"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\DECODER"::decode($resource, $offset, $length, $config);
        return $data;
    };
    return ($providers["?array<int, mixed>"])($resource, 0, -1);
};
