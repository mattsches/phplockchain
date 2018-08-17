<?php

namespace Mattsches;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidDigestLength;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSignature;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Asymmetric\Crypto;
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
     * @param string $message
     * @param string $privateKey
     * @param string $recipientPublicKey
     * @return string
     */
    public static function signTransaction(string $message, string $privateKey, string $recipientPublicKey): string
    {
        try {
            $signature = Crypto::signAndEncrypt(
                new HiddenString($message),
                self::getPrivateKeyAsObject($privateKey),
                self::getPublicKeyAsObject($recipientPublicKey)
            );
        } catch (InvalidKey $e) {
            throw new \InvalidArgumentException('Invalid private key: ' . $e->getMessage());
        } catch (InvalidType $e) {
            throw new \InvalidArgumentException('Invalid type');
        } catch (CannotPerformOperation $e) {
            throw new \InvalidArgumentException('Cannot perform operation');
        } catch (InvalidDigestLength $e) {
            throw new \InvalidArgumentException('Invalid digest length');
        } catch (InvalidMessage $e) {
            throw new \InvalidArgumentException('Invalid message');
        } catch (\SodiumException $e) {
            throw new \InvalidArgumentException($e->getMessage() . ' ' . $privateKey . ' ' . $recipientPublicKey);
        }

        return $signature;
    }

    /**
     * @param $privateKey
     * @return SignaturePublicKey
     * @throws InvalidKey
     * @throws \SodiumException
     */
    public static function getPrivateKeyAsObject($privateKey): SignatureSecretKey
    {
        if ($privateKey instanceof SignatureSecretKey) {
            return $privateKey;
        }

        return new SignatureSecretKey(new HiddenString(sodium_hex2bin($privateKey)));
    }

    /**
     * @param $publicKey
     * @return SignaturePublicKey
     * @throws InvalidKey
     * @throws \SodiumException
     */
    public static function getPublicKeyAsObject($publicKey): SignaturePublicKey
    {
        if ($publicKey instanceof SignaturePublicKey) {
            return $publicKey;
        }

        return new SignaturePublicKey(new HiddenString(sodium_hex2bin($publicKey)));
    }

    /**
     * @param string $message
     * @param string $publicKey
     * @param string $signature
     * @return bool
     */
    public static function verifyTransaction(string $message, string $publicKey, string $signature): bool
    {
        try {
            $verified = Crypto::verify(
                $message,
                new SignaturePublicKey(new HiddenString(sodium_hex2bin($publicKey))),
                $signature
            );
        } catch (InvalidKey $e) {
            $verified = false;
        } catch (InvalidSignature $e) {
            $verified = false;
        } catch (InvalidType $e) {
            $verified = false;
        } catch (\SodiumException $e) {
            $verified = false;
        }

        return $verified;
    }

    /**
     * @param string $message
     * @param SignaturePublicKey $senderPublicKey
     * @param SignatureSecretKey $recipientPrivateKey
     * @return string
     */
    public static function verifyAndDecryptTransaction(
        string $message,
        SignaturePublicKey $senderPublicKey,
        SignatureSecretKey $recipientPrivateKey
    ): string {
        try {
            $decrypted = Crypto::verifyAndDecrypt($message, $senderPublicKey, $recipientPrivateKey);
        } catch (CannotPerformOperation|InvalidDigestLength|InvalidKey|InvalidMessage|InvalidSignature|InvalidType $e) {
            return $e->getMessage();
        }

        return $decrypted->getString();
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
