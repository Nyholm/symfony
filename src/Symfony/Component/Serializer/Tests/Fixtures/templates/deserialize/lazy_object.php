<?php

/**
 * @param resource $resource
 * @return Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy {
        $boundaries = "\\SPLITTER"::splitDict($resource, $offset, $length);
        $properties = [];
        foreach ($boundaries as $k => $b) {
            if ("id" === $k) {
                $properties["id"] = static function () use ($resource, $b, $config, $instantiator, &$providers): mixed {
                    return ($providers["int"])($resource, $b[0], $b[1]);
                };
                continue;
            }
            if ("name" === $k) {
                $properties["name"] = static function () use ($resource, $b, $config, $instantiator, &$providers): mixed {
                    return ($providers["string"])($resource, $b[0], $b[1]);
                };
                continue;
            }
        }
        return $instantiator->instantiate("Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy", $properties);
    };
    $providers["int"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\DECODER"::decode($resource, $offset, $length, $config);
        try {
            return (int) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"int\"", get_debug_type($data)));
        }
    };
    $providers["string"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\DECODER"::decode($resource, $offset, $length, $config);
        try {
            return (string) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"string\"", get_debug_type($data)));
        }
    };
    return ($providers["Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\ClassicDummy"])($resource, 0, -1);
};
