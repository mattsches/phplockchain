<?php

use Mattsches\Util;
use ParagonIE\Halite\Asymmetric\PublicKey;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class UtilTest
 */
class UtilTest extends \Codeception\Test\Unit
{

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     * @throws \ParagonIE\Halite\Alerts\InvalidType
     */
    public function testGetTransactionSignature()
    {
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $privateKey = $keyPair->getSecretKey();
        $publicKey = $keyPair->getPublicKey();
        $result = Util::getTransactionSignature($publicKey, $publicKey, 10, $privateKey);
        $this->assertStringEndsWith('==', $result);
    }

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testGetKeyAsString()
    {
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $privateKey = $keyPair->getSecretKey();
        $publicKey = $keyPair->getPublicKey();
        $this->assertSame(128, strlen(Util::getKeyAsString($privateKey)));
        $this->assertSame(64, strlen(Util::getKeyAsString($publicKey)));
    }

    /**
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testCreateSignatureKeypair()
    {
        $this->assertInstanceOf(SignatureKeyPair::class, Util::createSignatureKeypair());
    }

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testGetPublicKeyAsObject()
    {
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $publicKey = $keyPair->getPublicKey();
        $result = Util::getPublicKeyAsObject(Util::getKeyAsString($publicKey));
        $this->assertInstanceOf(PublicKey::class, $result);
    }

    /**
     *
     */
    public function testSignTransaction()
    {
        $this->markTestSkipped('method will probably be removed');
    }
}
