<?php

/**
 * @param iterable<int, mixed> $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    $normalized = [];
    foreach ($data as $key_0 => $value_0) {
        $normalized[$key_0] = $value_0;
    }
    "\\ENCODER"::encode($resource, $normalized, $config);
};
