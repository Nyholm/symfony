<?php

/**
 * @param resource $resource
 * @return Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $resource, \Symfony\Component\Serializer\Deserialize\Config\DeserializeConfig $config, \Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["Symfony\\Component\\Serializer\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return "Symfony\\Component\\Serializer\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::from($data);
        } catch (\ValueError $e) {
            throw new \Symfony\Component\Serializer\Exception\UnexpectedValueException(sprintf("Unexpected \"%s\" value for \"Symfony\\Component\\Serializer\\Tests\\Fixtures\\Enum\\DummyBackedEnum\" backed enumeration.", $data));
        }
    };
    return ($providers["Symfony\\Component\\Serializer\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])("\\DECODER"::decode($resource, 0, -1, $config));
};
