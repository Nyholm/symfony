<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Php;

/**
 * Optimizes a PHP syntax tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Optimizer
{
    /**
     * @template T of PhpNodeInterface|list<PhpNodeInterface>
     *
     * @param T $subject
     *
     * @return T
     */
    public function optimize(PhpNodeInterface|array $subject): PhpNodeInterface|array
    {
        /** @var T $optimized */
        $optimized = $subject instanceof PhpNodeInterface ? $subject->optimize($this) : $this->optimizeNodeCollection($subject);

        return $optimized;
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function optimizeNodeCollection(array $nodes): array
    {
        return $this->mergeResourceStringFwrites($nodes);
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function mergeResourceStringFwrites(array $nodes): array
    {
        if (!array_is_list($nodes)) {
            return $nodes;
        }

        $createFwriteExpression = fn (string $content) => new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([
            new VariableNode('resource'),
            new ScalarNode($content),
        ])));

        $stringContent = '';
        $mergedNodes = [];

        foreach ($nodes as $node) {
            if (!$this->isStringResourceFwrite($node)) {
                if ('' !== $stringContent) {
                    $mergedNodes[] = $createFwriteExpression($stringContent);
                    $stringContent = '';
                }

                $mergedNodes[] = $node;

                continue;
            }

            /**
             * @var ExpressionNode<FunctionCallNode> $node
             * @var ScalarNode                       $stringArgument
             */
            $stringArgument = $node->node->arguments->arguments[1];

            $stringContent = $stringContent.$stringArgument->value;
        }

        if ('' !== $stringContent) {
            $mergedNodes[] = $createFwriteExpression($stringContent);
        }

        /** @var list<PhpNodeInterface> $optimizedNodes */
        $optimizedNodes = array_map($this->optimize(...), $mergedNodes);

        return $optimizedNodes;
    }

    private function isStringResourceFwrite(PhpNodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof FunctionCallNode || '\\fwrite' !== $currentNode->name) {
            return false;
        }

        $resourceArgument = $currentNode->arguments->arguments[0] ?? null;
        $dataArgument = $currentNode->arguments->arguments[1] ?? null;

        return $resourceArgument instanceof VariableNode && 'resource' === $resourceArgument->name
            && $dataArgument instanceof ScalarNode && \is_string($dataArgument->value);
    }
}
