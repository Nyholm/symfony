<?php

/**
 * @param resource $resource
 * @return ?Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy"] = static function (?array $data) use ($config, $instantiator, &$providers): ?Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy {
        if (null === $data) {
            return null;
        }
        $properties = [];
        if (isset($data["id"])) {
            $properties["id"] = static function () use ($data, $config, $instantiator, &$providers): mixed {
                return ($providers["int"])($data["id"]);
            };
        }
        if (isset($data["name"])) {
            $properties["name"] = static function () use ($data, $config, $instantiator, &$providers): mixed {
                return ($providers["string"])($data["name"]);
            };
        }
        return $instantiator->instantiate("Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy", $properties);
    };
    $providers["int"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return (int) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"int\"", get_debug_type($data)));
        }
    };
    $providers["string"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return (string) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"string\"", get_debug_type($data)));
        }
    };
    return ($providers["?Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy"])("\\DECODER"::decode($resource, 0, -1, $config));
};
