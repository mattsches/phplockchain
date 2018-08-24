<?php

namespace Mattsches;

use GuzzleHttp\Client as HttpClient;
use ParagonIE\Halite\SignatureKeyPair;
use Ramsey\Uuid\Uuid;

/**
 * Class InitialClient
 * @package Mattsches
 */
class InitialClient extends Client
{
    /**
     * InitialClient constructor.
     * @param SignatureKeyPair $keyPair
     * @param HttpClient $httpClient
     * @param int $difficulty
     */
    public function __construct(SignatureKeyPair $keyPair, HttpClient $httpClient, int $difficulty)
    {
        parent::__construct($keyPair, $httpClient);
        $this->blockChain = $this->initBlockChain($difficulty);
        try {
            $this->addGenesisBlock();
        } catch (\Exception $exception) {
            die($exception->getMessage());
        }

    }

    /**
     * @return bool
     */
    public function isMaster(): bool
    {
        return true;
    }

    /**
     * @param int $difficulty
     * @return BlockChain
     */
    private function initBlockChain(int $difficulty): BlockChain
    {
        return new BlockChain($difficulty);
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    private function addGenesisBlock(): void
    {
        $amount = 10;
        $publicKeyAsString = Util::getKeyAsString($this->keyPair->getPublicKey());
        $message = $publicKeyAsString.$publicKeyAsString.$amount;
        $signature = Util::signTransaction(
            $message,
            Util::getKeyAsString($this->keyPair->getSecretKey()),
            $publicKeyAsString
        );
        $this->addTransaction(
            new Transaction(Uuid::uuid4(), $this->keyPair->getPublicKey(), $this->keyPair->getPublicKey(), $amount, $signature)
        );
        $this->blockChain->addBlock($this->currentTransactions, 100, '1', time());
        $this->currentTransactions = [];
    }
}
