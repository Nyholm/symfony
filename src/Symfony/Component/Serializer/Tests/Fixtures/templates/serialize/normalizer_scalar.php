<?php

/**
 * @param int $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    $normalized = $data;
    "\\ENCODER"::encode($resource, $normalized, $config);
};
