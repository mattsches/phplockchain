<?php

namespace Mattsches;

use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\PublicKey;
use ParagonIE\Halite\Asymmetric\SignaturePublicKey;
use ParagonIE\Halite\Asymmetric\SignatureSecretKey;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\Key;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class Util
 * @package Mattsches
 */
final class Util
{
    /**
     * @param $sender
     * @param $recipient
     * @param int $amount
     * @param SignatureSecretKey $privateKey
     * @return string
     * @throws InvalidType
     * @throws \SodiumException
     */
    public static function getTransactionSignature(
        $sender,
        $recipient,
        int $amount,
        SignatureSecretKey $privateKey
    ): string {
        $message = self::getKeyAsString($sender).self::getKeyAsString($recipient).$amount;

        return Crypto::sign($message, $privateKey);
    }

    /**
     * @param string|Key $key
     * @return string
     * @throws \SodiumException
     */
    public static function getKeyAsString($key): string
    {
        if ($key instanceof Key) {
            return sodium_bin2hex($key->getRawKeyMaterial());
        }

        return (string)$key;
    }

    /**
     * @param $publicKey
     * @return PublicKey
     * @throws InvalidKey
     * @throws \SodiumException
     */
    public static function getPublicKeyAsObject($publicKey): PublicKey
    {
        if ($publicKey instanceof PublicKey) {
            return $publicKey;
        }

        return new SignaturePublicKey(new HiddenString(sodium_hex2bin($publicKey)));
    }

    /**
     * @param string $message
     * @param string $privateKey
     * @return string
     */
    public static function signTransaction(string $message, string $privateKey): string
    {
        try {
            $signature = Crypto::sign(
                $message,
                new SignatureSecretKey(new HiddenString(sodium_hex2bin($privateKey)))
            );
        } catch (InvalidKey|\SodiumException $e) {
            throw new \InvalidArgumentException('Invalid private key');
        } catch (InvalidType $e) {
            throw new \InvalidArgumentException('Invalid type');
        }

        return $signature;
    }

    /**
     * @return SignatureKeyPair
     * @throws InvalidKey
     */
    public static function createSignatureKeypair(): SignatureKeyPair
    {
        return KeyFactory::generateSignatureKeyPair();
    }
}
