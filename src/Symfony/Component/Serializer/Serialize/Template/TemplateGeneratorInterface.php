<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Php\PhpNodeInterface;
use Symfony\Component\Serializer\Serialize\Config\SerializeConfig;
use Symfony\Component\Serializer\Serialize\DataModel\DataModelNodeInterface;

/**
 * Generates a serialization template PHP syntax tree based on a given data model.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface TemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    public function generate(DataModelNodeInterface $node, SerializeConfig $config, array $context): array;
}
