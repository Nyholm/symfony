<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encryption\Tests\Encryption\Phpseclib;

use phpseclib3\Crypt\AES;
use Symfony\Component\Encryption\EncryptionInterface;
use Symfony\Component\Encryption\KeyInterface;
use Symfony\Component\Encryption\Phpseclib\PhpseclibEncryption;
use Symfony\Component\Encryption\Phpseclib\PhpseclibKey;
use Symfony\Component\Encryption\Tests\AbstractEncryptionTest;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PhpseclibEncryptionTest extends AbstractEncryptionTest
{
    protected function getEncryption(): EncryptionInterface
    {
        if (!class_exists(AES::class)) {
            $this->markTestSkipped('Package phpseclib/phpseclib is not installed.');
        }

        return new PhpseclibEncryption();
    }

    protected function createPrivateKey(KeyInterface $key): KeyInterface
    {
        return PhpseclibKey::fromPrivateKey($key->getPrivateKey());
    }
}
