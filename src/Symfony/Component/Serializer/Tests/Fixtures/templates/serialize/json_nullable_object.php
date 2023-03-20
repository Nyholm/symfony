<?php

/**
 * @param ?Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, \Symfony\Component\Serializer\Serialize\Config\SerializeConfig $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, "{\"id\":");
        \fwrite($resource, \json_encode($data->id, $config->json()->flags()));
        \fwrite($resource, ",\"name\":");
        \fwrite($resource, \json_encode($data->name, $config->json()->flags()));
        \fwrite($resource, "}");
    }
};
