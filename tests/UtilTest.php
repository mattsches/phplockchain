<?php

use Mattsches\Util;
use ParagonIE\Halite\Asymmetric\PublicKey;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class UtilTest
 */
class UtilTest extends \Codeception\Test\Unit
{

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
     * @throws \ParagonIE\Halite\Alerts\InvalidDigestLength
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     * @throws \ParagonIE\Halite\Alerts\InvalidMessage
     * @throws \ParagonIE\Halite\Alerts\InvalidType
     */
//    public function testGetTransactionSignature(): void
//    {
//        $this->markTestSkipped();
//        $keyPair = KeyFactory::generateSignatureKeyPair();
//        $privateKey = $keyPair->getSecretKey();
//        $publicKey = $keyPair->getPublicKey();
//        $result = Util::getTransactionSignature($publicKey, $publicKey, 10, $privateKey);
//        $this->assertStringEndsWith('==', $result);
//    }

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testGetKeyAsString(): void
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
    public function testCreateSignatureKeypair(): void
    {
        $this->assertInstanceOf(SignatureKeyPair::class, Util::createSignatureKeypair());
    }

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testGetPublicKeyAsObject(): void
    {
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $publicKey = $keyPair->getPublicKey();
        $result = Util::getPublicKeyAsObject(Util::getKeyAsString($publicKey));
        $this->assertInstanceOf(PublicKey::class, $result);
    }

    /**
     *
     */
    public function testSignTransaction(): void
    {
        $amount = 11;
        $senderKeyPair = KeyFactory::generateSignatureKeyPair();
        $senderPrivKey = Util::getKeyAsString($senderKeyPair->getSecretKey());
        $senderPubKey = Util::getKeyAsString($senderKeyPair->getPublicKey());
        $recipientKeyPair = KeyFactory::generateSignatureKeyPair();
        $recipientPubKey = Util::getKeyAsString($recipientKeyPair->getPublicKey());
        $message = $senderPubKey.$recipientPubKey.$amount;
        $signature = Util::signTransaction($message, $senderPrivKey, $recipientPubKey);
        $this->assertSame(424, strlen($signature));

        /** @var HiddenString $result */
        $result = Util::verifyAndDecryptTransaction($signature, $senderKeyPair->getPublicKey(), $recipientKeyPair->getSecretKey());
        $this->assertSame($message, $result);
//        $this->markTestSkipped('method will probably be removed');
    }

    public function testVerifyTransaction()
    {
        $amount = 11;
        $senderKeyPair = KeyFactory::generateSignatureKeyPair();
        $senderPrivKey = Util::getKeyAsString($senderKeyPair->getSecretKey());
        $senderPubKey = Util::getKeyAsString($senderKeyPair->getPublicKey());
        $recipientKeyPair = KeyFactory::generateSignatureKeyPair();
        $recipientPubKey = Util::getKeyAsString($recipientKeyPair->getPublicKey());
        $message = $senderPubKey.$recipientPubKey.$amount;
        $signature = Util::signTransaction($message, $senderPrivKey, $recipientPubKey);
        Util::verifyTransaction($message, $senderPubKey, $signature);
    }

    /**
     *
     */
    public function testSignTransaction2(): void
    {
        $amount = 11;
        $senderPrivKey = '518c4b0d28d58c10298f48462a905bbba02710bd6db146c1a8ed943cc2fd007843a89226bcbdd764facc862c4c9dd2908195409990d8ae2052b99ce42903b73d';
        $senderPubKey = '43a89226bcbdd764facc862c4c9dd2908195409990d8ae2052b99ce42903b73d';
        $recipientPubKey = '219da15c8034339fcff1b4858c5ca0b53dad43a842e644184c531b6bf23a1d80';
        $recipientPrivKey = '3666284f9ae93f0ead02511b37f5fead1e3c2e75c0c6483fdafb56f01c4e898b219da15c8034339fcff1b4858c5ca0b53dad43a842e644184c531b6bf23a1d80';
        $message = $senderPubKey.$recipientPubKey.$amount;
        $signature = Util::signTransaction($message, $senderPrivKey, $recipientPubKey);
        $this->assertSame(424, strlen($signature));

        /** @var HiddenString $result */
        $result = Util::verifyAndDecryptTransaction($signature, Util::getPublicKeyAsObject($senderPubKey), Util::getPrivateKeyAsObject($recipientPrivKey));
        $this->assertSame($message, $result);
//        $this->markTestSkipped('method will probably be removed');
    }

    /**
     *
     */
    public function testSignTransaction3(): void
    {
        $amount = 11;
        $senderPrivKey = '518c4b0d28d58c10298f48462a905bbba02710bd6db146c1a8ed943cc2fd007843a89226bcbdd764facc862c4c9dd2908195409990d8ae2052b99ce42903b73d';
        $senderPubKey = '43a89226bcbdd764facc862c4c9dd2908195409990d8ae2052b99ce42903b73d';
        $message = $senderPubKey.$senderPubKey.$amount;
        $signature = Util::signTransaction($message, $senderPrivKey, $senderPubKey);
        $this->assertSame(424, strlen($signature));

        /** @var HiddenString $result */
        $result = Util::verifyAndDecryptTransaction($signature, Util::getPublicKeyAsObject($senderPubKey), Util::getPrivateKeyAsObject($senderPrivKey));
        $this->assertSame($message, $result);
//        $this->markTestSkipped('method will probably be removed');
    }
}
