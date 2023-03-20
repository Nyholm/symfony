<?php

/**
 * @param ?array<string, mixed> $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        "\\ENCODER"::encode($resource, null, $config);
        return;
    }
    $normalized = [];
    foreach ($data as $key_0 => $value_0) {
        $normalized[$key_0] = $value_0;
    }
    "\\ENCODER"::encode($resource, $normalized, $config);
};
