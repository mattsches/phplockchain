<?php

use Mattsches\Transaction;
use Mattsches\Util;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\KeyFactory;

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
        $this->markTestSkipped('todo do we still need this method?');
        $this->assertSame('10foobar', $this->transaction->getHashableString());
    }

    /**
     * @test
     **/
    public function itShouldGetJsonSerializedRepresentation(): void
    {
        $expected = [
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'amount' => 10,
            'signature' => 'baz',
        ];
        $this->assertSame($expected, $this->transaction->jsonSerialize());
    }

    /**
     * @test
     * @throws SodiumException
     * @throws \ParagonIE\Halite\Alerts\InvalidType
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function itShouldBeAValidTransaction(): void
    {
        $this->markTestSkipped('Wait until transaction validation is implemented.');
        $transaction = null;
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $privateKey = $keyPair->getSecretKey();
        $publicKey = $keyPair->getPublicKey();
        $anotherPublicKey = KeyFactory::generateSignatureKeyPair()->getPublicKey();
        $signature = Crypto::sign(
            10,
            $privateKey
        );
        $transaction = new Transaction(
            $publicKey,
            $anotherPublicKey,
            10,
            $signature
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
