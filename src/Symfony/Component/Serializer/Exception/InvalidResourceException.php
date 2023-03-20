<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class InvalidResourceException extends UnexpectedValueException
{
    /**
     * @param resource $resource
     */
    public function __construct(mixed $resource)
    {
        parent::__construct(sprintf('Resource "%s" is not valid.', stream_get_meta_data($resource)['uri']));
    }
}
