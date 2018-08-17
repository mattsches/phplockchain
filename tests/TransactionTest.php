<?php

use Mattsches\Transaction;
use Mattsches\Util;
use ParagonIE\Halite\KeyFactory;
use Ramsey\Uuid\Uuid;

/**
 * Class TransactionTest
 */
class TransactionTest extends \Codeception\Test\Unit
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var string
     */
    private $sender;

    /**
     * @var string
     */
    private $recipient;

    /**
     *
     */
    protected function _before()
    {
        $amount = 10;
        $signature = 'baz';
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $publicKey = $keyPair->getPublicKey();
        $anotherPublicKey = KeyFactory::generateSignatureKeyPair()->getPublicKey();
        $this->sender = Util::getKeyAsString($publicKey);
        $this->recipient = Util::getKeyAsString($anotherPublicKey);
        $this->transaction = new Transaction($publicKey, $anotherPublicKey, $amount, $signature);
    }

    /**
     * @test
     **/
    public function itShouldGetHashableString(): void
    {
        $result = $this->transaction->getHashableString();
        $this->assertStringStartsWith('10', $result);
        $this->assertSame(130, strlen($result));
    }

    /**
     * @test
     **/
    public function itShouldGetJsonSerializedRepresentation(): void
    {
        $result = $this->transaction->jsonSerialize();
        $this->assertArrayHasKey('txid', $result);
        $this->assertArrayHasKey('sender', $result);
        $this->assertArrayHasKey('recipient', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('signature', $result);
        $this->assertTrue(Uuid::isValid($result['txid']));
        $this->assertSame($this->sender, $result['sender']);
        $this->assertSame($this->recipient, $result['recipient']);
        $this->assertSame(10, $result['amount']);
        $this->assertSame('baz', $result['signature']);
    }

    /**
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function testVerifyAndDecrypt(): void
    {
        $transaction = null;
        $senderKeyPair = KeyFactory::generateSignatureKeyPair();
        $senderPrivKey = $senderKeyPair->getSecretKey();
        $senderPubKey = $senderKeyPair->getPublicKey();
        $recipientKeyPair = KeyFactory::generateSignatureKeyPair();
        $recipientPrivKey = $recipientKeyPair->getSecretKey();
        $recipientPubKey = $recipientKeyPair->getPublicKey();
        $amount = 10;
        $message = Util::getKeyAsString($senderPubKey).Util::getKeyAsString($recipientPubKey).$amount;
        $signature = Util::signTransaction(
            $message,
            Util::getKeyAsString($senderPrivKey),
            Util::getKeyAsString($recipientPubKey)
        );
        $transaction = new Transaction(
            $senderPubKey,
            $recipientPubKey,
            $amount,
            $signature
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertTrue($transaction->verifyAndDecrypt($recipientPrivKey));
    }
}
