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

use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Type\Type;

/**
 * Creates a data model graph representation of a given type.
 *
 * This data model will be used by the {@see Symfony\Component\Serializer\Serialize\Template\TemplateGeneratorInterface}
 * to generate a PHP serialization template.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface DataModelBuilderInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function build(Type $type, PhpNodeInterface $accessor, SerializeConfig $config, array $context = []): DataModelNodeInterface;
}
