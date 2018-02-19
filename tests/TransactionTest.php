<?php

use Mattsches\Transaction;
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
     *
     */
    protected function _before()
    {
        $sender = 'foo';
        $recipient = 'bar';
        $amount = 10;
        $signature = 'baz';
        $this->transaction = new Transaction($sender, $recipient, $amount, $signature);
    }

    /**
     * @test
     **/
    public function itShouldGetHashableString()
    {
        $this->assertSame('10foobar', $this->transaction->getHashableString());
    }

    /**
     * @test
     **/
    public function itShouldGetJsonSerializedRepresentation()
    {
        $expected = [
            'sender' => 'foo',
            'recipient' => 'bar',
            'amount' => 10,
            'signature' => 'baz',
        ];
        $this->assertSame($expected, $this->transaction->jsonSerialize());
    }

    /**
     * @test
     */
    public function itShouldBeAValidTransaction()
    {
        $this->markTestSkipped('Wait until transaction validation is implemented.');
        $transaction = null;
        $keyPair = KeyFactory::generateSignatureKeyPair();
        $privateKey = $keyPair->getSecretKey();
        $publicKey = $keyPair->getPublicKey();
        $signature = Crypto::sign(
            10,
            $privateKey
        );
        $transaction = new Transaction(
            sodium_bin2hex($publicKey->getRawKeyMaterial()),
            '6507efb8cdaa6a67af4f51a97dcc97d3d109562ae4f3b886d963e15e6670a33f',
            10,
            $signature
        );
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
}
