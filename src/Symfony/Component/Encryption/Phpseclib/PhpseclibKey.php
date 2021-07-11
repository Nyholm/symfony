<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encryption\Phpseclib;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use Symfony\Component\Encryption\Exception\InvalidKeyException;
use Symfony\Component\Encryption\KeyInterface;

/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class PhpseclibKey implements KeyInterface
{
    /**
     * @var string|null
     */
    private $secret;

    /**
     * @var PrivateKey|null
     */
    private $privateKey;

    /**
     * @var PublicKey|null
     */
    private $publicKey;

    public static function create(string $secret, PrivateKey $private): self
    {
        $key = new self();
        $key->secret = $secret;
        $key->privateKey = $private;

        return $key;
    }

    public static function fromPrivateKey(PrivateKey $privateKey): self
    {
        $key = new self();
        $key->privateKey = $privateKey;

        return $key;
    }

    public static function fromPublicKey(PublicKey $publicKey): self
    {
        $key = new self();
        $key->publicKey = $publicKey;

        return $key;
    }

    public function extractPublicKey(): KeyInterface
    {
        return self::fromPublicKey($this->getPublicKey());
    }

    public function __serialize(): array
    {
        return [$this->secret, $this->privateKey, $this->publicKey];
    }

    public function __unserialize(array $data): void
    {
        [$this->secret, $this->privateKey, $this->publicKey] = $data;
    }

    public function getSecret(): string
    {
        if (null === $this->secret) {
            throw new InvalidKeyException('This key does not have a secret.');
        }

        return $this->secret;
    }

    public function getPrivateKey(): PrivateKey
    {
        if (null === $this->privateKey) {
            throw new InvalidKeyException('This key does not have a private key.');
        }

        return $this->privateKey;
    }

    public function getPublicKey(): PublicKey
    {
        if (null !== $this->publicKey) {
            return $this->publicKey;
        }
        if (null === $this->privateKey) {
            throw new InvalidKeyException('This key does not have a public key.');
        }

        return $this->privateKey->getPublicKey();
    }
}
