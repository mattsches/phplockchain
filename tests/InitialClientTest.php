<?php

use Mattsches\BlockChain;
use Mattsches\Client;
use Mattsches\InitialClient;
use Mattsches\Util;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class InitialClientTest
 */
class InitialClientTest extends \Codeception\Test\Unit
{
    /**
     * @var
     */
    private $client;

    /**
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    protected function _before()
    {
        $keyPair = Util::createSignatureKeypair();
        $this->client = new InitialClient($keyPair, 4);
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
