<?php

/**
 * @param resource $resource
 * @return array<int, mixed>
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["array<int, mixed>"] = static function (?iterable $data) use ($config, $instantiator, &$providers): array {
        $iterable = static function (iterable $data) use ($config, $instantiator, &$providers): iterable {
            foreach ($data as $k => $v) {
                yield $k => ($providers["mixed"])($v);
            }
        };
        return \iterator_to_array(($iterable)($data));
    };
    $providers["mixed"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        return $data;
    };
    return ($providers["array<int, mixed>"])("\\DECODER"::decode($resource, 0, -1, $config));
};
