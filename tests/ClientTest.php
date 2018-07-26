<?php

use Mattsches\BlockChain;
use Mattsches\Client;
use Mattsches\Util;
use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class ClientTest
 */
class ClientTest extends \Codeception\Test\Unit
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
}
