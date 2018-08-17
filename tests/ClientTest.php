<?php

use Codeception\Stub;
use Mattsches\Client;
use Mattsches\InitialClient;
use Mattsches\Transaction;
use Mattsches\Util;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class ClientTest
 */
class ClientTest extends \Codeception\Test\Unit
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    protected function _before()
    {
        $keyPair = Util::createSignatureKeypair();
        $this->client = new Client($keyPair);
    }

    /**
     * @test
     */
    public function itShouldBeInitialized(): void
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    /**
     * @test
     */
    public function itShouldGetBlockChain(): void
    {
        $this->assertNull($this->client->getBlockChain());
    }

    /**
     * @test
     */
    public function itShouldGetKeyPair(): void
    {
        $this->assertInstanceOf(SignatureKeyPair::class, $this->client->getKeyPair());
    }

    /**
     * @test
     */
    public function itShouldVerifyAndDecryptTransaction()
    {
        $client = new InitialClient(Util::createSignatureKeypair(), 4);
        $transaction = Stub::make(Transaction::class, [
            'getTxid' => 'txid',
            'verifyAndDecrypt' => true,
        ]);
        $client->getBlockChain()->addTransaction($transaction);
        $client->getBlockChain()->addBlock(1, 'foo');
        $this->assertTrue($client->verifyAndDecryptTransaction('txid'));
    }

    /**
     * @test
     */
    public function itShouldTryToVerifyANonexistantTransaction()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction not found');
        $client = new InitialClient(Util::createSignatureKeypair(), 4);
        $transaction = Stub::make(Transaction::class, [
            'getTxid' => 'foobar',
        ]);
        $client->getBlockChain()->addTransaction($transaction);
        $client->getBlockChain()->addBlock(1, 'foo');
        $client->verifyAndDecryptTransaction('txid');
    }
}
