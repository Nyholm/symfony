<?php

/**
 * @param ?Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        "\\ENCODER"::encode($resource, null, $config);
        return;
    }
    $normalized["id"] = $data->id;
    $normalized["name"] = $data->name;
    "\\ENCODER"::encode($resource, $normalized, $config);
};
