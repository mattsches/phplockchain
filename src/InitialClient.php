<?php

namespace Mattsches;

use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class InitialClient
 * @package Mattsches
 */
class InitialClient extends Client
{
    /**
     * InitialClient constructor.
     * @param SignatureKeyPair $keyPair
     * @param int $difficulty
     */
    public function __construct(SignatureKeyPair $keyPair, int $difficulty)
    {
        parent::__construct($keyPair, $this->initBlockChain($difficulty));
        try {
            $this->addGenesisBlock();
        } catch (\Exception $exception) {
            die($exception->getMessage());
        }

    }

    /**
     * @param int $difficulty
     * @return $this
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
            new Transaction($this->keyPair->getPublicKey(), $this->keyPair->getPublicKey(), $amount, $signature)
        );
        $this->blockChain->addBlock($this->currentTransactions, 100, '1');
        $this->currentTransactions = [];
    }
}
