<?php

/**
 * @param resource $resource
 * @return ?string
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?string"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\DECODER"::decode($resource, $offset, $length, $config);
        if (null === $data) {
            return null;
        }
        try {
            return (string) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"string\"", get_debug_type($data)));
        }
    };
    return ($providers["?string"])($resource, 0, -1);
};
