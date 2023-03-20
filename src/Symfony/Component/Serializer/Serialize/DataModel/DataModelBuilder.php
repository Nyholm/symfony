<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\DataModel;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MaxDepthException;
use Symfony\Component\Serializer\Php\ArgumentsNode;
use Symfony\Component\Serializer\Php\FunctionCallNode;
use Symfony\Component\Serializer\Php\MethodCallNode;
use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Php\PropertyNode;
use Symfony\Component\Serializer\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Serializer\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Serialize\VariableNameScoperTrait;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class DataModelBuilder implements DataModelBuilderInterface
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private readonly ContainerInterface $runtimeServices,
    ) {
    }

    public function build(Type $type, PhpNodeInterface $accessor, SerializeConfig $config, array $context = []): DataModelNodeInterface
    {
        if ($type->isObject() && $type->hasClass()) {
            $className = $type->className();

            $context['depth_counters'][$className] ??= 0;
            ++$context['depth_counters'][$className];

            if ($context['depth_counters'][$className] > $config->maxDepth()) {
                throw new MaxDepthException($className, $config->maxDepth());
            }

            $propertiesNodes = [];

            foreach ($this->propertyMetadataLoader->load($className, $config, ['original_type' => $type] + $context) as $serializedName => $propertyMetadata) {
                $propertyAccessor = new PropertyNode($accessor, $propertyMetadata->name());

                foreach ($propertyMetadata->formatters() as $f) {
                    $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                    $functionName = null === $reflection->getClosureScopeClass()
                        ? $reflection->getName()
                        : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                    $arguments = [];
                    foreach ($reflection->getParameters() as $i => $parameter) {
                        if (0 === $i) {
                            $arguments[] = $propertyAccessor;

                            continue;
                        }

                        $parameterType = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
                        if (is_a($parameterType, SerializeConfig::class, allow_string: true)) {
                            $arguments[] = new VariableNode('config');

                            continue;
                        }

                        $argumentName = sprintf('%s[%s]', $functionName, $parameter->name);
                        if ($this->runtimeServices->has($argumentName)) {
                            $arguments[] = new MethodCallNode(new VariableNode('services'), 'get', new ArgumentsNode([new PhpScalarNode($argumentName)]));

                            continue;
                        }

                        throw new LogicException(sprintf('Cannot resolve "%s" argument of "%s()".', $parameter->name, $functionName));
                    }

                    $propertyAccessor = new FunctionCallNode($functionName, new ArgumentsNode($arguments));
                }

                $propertiesNodes[$serializedName] = $this->build($propertyMetadata->type(), $propertyAccessor, $config, $context);
            }

            return new ObjectNode($accessor, $type, $propertiesNodes);
        }

        if ($type->isCollection() && ($type->isList() || $type->isDict())) {
            return new CollectionNode(
                $accessor,
                $type,
                $this->build($type->collectionValueType(), new VariableNode($this->scopeVariableName('value', $context)), $config, $context),
            );
        }

        return new ScalarNode($accessor, $type);
    }
}
