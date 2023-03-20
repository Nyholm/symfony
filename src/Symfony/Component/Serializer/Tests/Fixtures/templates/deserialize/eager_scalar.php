<?php

/**
 * @param resource $resource
 * @return int
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["int"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return (int) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"int\"", get_debug_type($data)));
        }
    };
    return ($providers["int"])("\\DECODER"::decode($resource, 0, -1, $config));
};
