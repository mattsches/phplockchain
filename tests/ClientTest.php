<?php

use Codeception\Stub;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mattsches\BlockChain;
use Mattsches\Client;
use Mattsches\InitialClient;
use Mattsches\Transaction;
use Mattsches\Util;
use ParagonIE\Halite\SignatureKeyPair;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

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
        $blockJson = '{
                "index": 1,
                "proof": 100,
                "previous_hash": "1",
                "transactions": [
                    {
                        "txid": "04c69797-7972-4298-af52-425c7e3a13c2",
                        "sender": "29aae4af5892b6a54028c6b236bda923f052623b0699d51f788a137a188d8bca",
                        "recipient": "29aae4af5892b6a54028c6b236bda923f052623b0699d51f788a137a188d8bca",
                        "amount": 10,
                        "signature": "MUIEAOl5-BGi-pX2CJPq0gEdJmJ2xTanUBO_PHzsDdjVnfVXhtvfANwgZnPn87VE09z9reJno0c1hr7A6rH38GTZShp_yIMWrC1P898QI1e1L1CUMeWwCAo5H44bHOVhyO63qL3m1KNC_85-jjIGjBMf41nF3XILL3RP_ObEDTMBm76THSyJEy8OBY7RawlXMFdn_nxteSPIDUMPFQHw1KIulxKvi7JhizAVSMGV8U3dpGcz-alcb2voFfMKUY6b2w6Ut-lvj6R_jh_sd-nSnJbQPKJ9gF62sAdedfG1dUBh3QqnYoNYafgQM4LgzJ2J88CJ9gNQAJ_RNhRmjKWvbdJkhgNqNF3n1ZBPoTiEIhzjmQ_DQQFKlFi6fewcpWNIAODYdOzG16hcEvs2mLUt_Gpl53yeN0QiHI-OCVXF"
                    }
                ],
                "hash": "0b475fc3b68d2607b8d4f1072da6551e4ad3bec25fb8eda02a7d0a96710ed01a",
                "timestamp": 1535103340
        }';
        $keyPair = Util::createSignatureKeypair();
        $mock = new MockHandler([
            new Response(200, [], '{"difficulty": 4, "blocks": 1}'),
            new Response(200, [], $blockJson),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new \GuzzleHttp\Client(['handler' => $handler]);
        $this->client = new Client($keyPair, $httpClient);
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
        $this->assertInstanceOf(BlockChain::class, $this->client->getBlockChain());
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
//        $client = new InitialClient(Util::createSignatureKeypair(), 4);
        $transaction = Stub::make(Transaction::class, [
            'getTxid' => 'txid',
            'verifyAndDecrypt' => true,
        ]);
        $this->client->addTransaction($transaction);
        $this->client->getBlockChain()->addBlock($this->client->getCurrentTransactions(), 1, 'foo', time());
        $this->assertTrue($this->client->verifyAndDecryptTransaction('txid'));
    }

    /**
     * @test
     */
    public function itShouldTryToVerifyANonexistantTransaction()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction not found');
//        $client = new InitialClient(Util::createSignatureKeypair(), 4);
        $transaction = Stub::make(Transaction::class, [
            'getTxid' => 'foobar',
        ]);
        $this->client->addTransaction($transaction);
        $this->client->getBlockChain()->addBlock($this->client->getCurrentTransactions(), 1, 'foo', time());
        $this->client->verifyAndDecryptTransaction('txid');
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function itShouldAddATransaction(): void
    {
//        $client = new InitialClient(Util::createSignatureKeypair(), 4);
        /** @var Transaction $transaction */
        $transaction = Stub::make(Transaction::class);
        $result = $this->client->addTransaction($transaction);
        $this->assertSame(2, $result);
    }
}
