<?php

/**
 * @param resource $resource
 * @return mixed
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["mixed"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        return $data;
    };
    return ($providers["mixed"])("\\DECODER"::decode($resource, 0, -1, $config));
};
