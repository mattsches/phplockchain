<?php

use GuzzleHttp\Client;
use Mattsches\BlockChain;
use Mattsches\InitialClient;
use Mattsches\Util;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class InitialClientTest
 */
class InitialClientTest extends \Codeception\Test\Unit
{
    /**
     * @var InitialClient
     */
    private $client;

    /**
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    protected function _before()
    {
        $keyPair = Util::createSignatureKeypair();
        $httpClient = new Client();
        $this->client = new InitialClient($keyPair, $httpClient, 4);
    }

    /**
     * @test
     */
    public function itShouldBeInitialized(): void
    {
        $this->assertInstanceOf(InitialClient::class, $this->client);
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
}
