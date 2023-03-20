<?php

/**
 * @param resource $resource
 * @return iterable<string, mixed>
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["iterable<string, mixed>"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): iterable {
        $boundaries = "\\SPLITTER"::splitDict($resource, $offset, $length);
        $iterable = static function (mixed $resource, iterable $boundaries) use ($config, $instantiator, &$providers): iterable {
            foreach ($boundaries as $k => $b) {
                yield $k => ($providers["mixed"])($resource, $b[0], $b[1]);
            }
        };
        return ($iterable)($resource, $boundaries);
    };
    $providers["mixed"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\DECODER"::decode($resource, $offset, $length, $config);
        return $data;
    };
    return ($providers["iterable<string, mixed>"])($resource, 0, -1);
};
