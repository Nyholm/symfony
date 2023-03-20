<?php

/**
 * @param ?string $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        "\\ENCODER"::encode($resource, null, $config);
        return;
    }
    $normalized = $data;
    "\\ENCODER"::encode($resource, $normalized, $config);
};
