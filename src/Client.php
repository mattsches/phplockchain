<?php

namespace Mattsches;

use ParagonIE\Halite\SignatureKeyPair;

/**
 * Class Client
 * @package Mattsches
 */
class Client
{
    /**
     * @var BlockChain
     */
    protected $blockChain;

    /**
     * @var Transaction[]
     */
    protected $currentTransactions = [];

    /**
     * @var SignatureKeyPair
     */
    protected $keyPair;

    /**
     * Client constructor.
     * @param SignatureKeyPair $keyPair
     * @param Blockchain $blockChain
     */
    public function __construct(SignatureKeyPair $keyPair, Blockchain $blockChain = null)
    {
        $this->keyPair = $keyPair;
        $this->blockChain = $blockChain;
    }

    /**
     * @return BlockChain|null
     */
    public function getBlockChain(): ?BlockChain
    {
        return $this->blockChain;
    }

    /**
     * @param string $txid
     * @return bool
     * @throws \Exception
     */
    public function verifyAndDecryptTransaction(string $txid): bool
    {
        $transaction = $this->getBlockChain()->findTransaction($txid);
        if (!$transaction instanceof Transaction) {
            throw new \Exception('Transaction not found');
        }

        return $transaction->verifyAndDecrypt($this->getKeyPair()->getSecretKey());
    }

    /**
     * @return SignatureKeyPair
     */
    public function getKeyPair(): SignatureKeyPair
    {
        return $this->keyPair;
    }

    /**
     * @param Transaction $transaction The transaction that will be added
     * @return int Index of the block to which the transaction will be added, ie the next block
     */
    public function addTransaction(Transaction $transaction): int
    {
        $this->currentTransactions[] = $transaction;
        $latestBlock = $this->getBlockChain()->getLatestBlock();
        if ($latestBlock instanceof Block) {
            return $latestBlock->getIndex() + 1;
        }

        return 1;
    }

    /**
     * @return array
     */
    public function getCurrentTransactions(): array
    {
        return $this->currentTransactions;
    }

    /**
     * @return Block
     * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
     * @throws \SodiumException
     */
    public function mine(): Block
    {
        $latestBlock = $this->getBlockChain()->getLatestBlock();
        $previousHash = $latestBlock->calculateHash();
        $proof = $this->getBlockChain()->getProofOfWork($latestBlock->getProofOfWork(), $previousHash);
        //TODO miner reward (coinbase), make this more obvious:
        $clientPublicKey = $this->getKeyPair()->getPublicKey();
        $amount = 12;
        $signature = Util::signTransaction(
            Util::getKeyAsString($clientPublicKey).Util::getKeyAsString($clientPublicKey).$amount,
            Util::getKeyAsString($this->getKeyPair()->getSecretKey()),
            Util::getKeyAsString($clientPublicKey)
        );
        $this->addTransaction(
            new Transaction(
                $clientPublicKey,
                $clientPublicKey,
                $amount,
                $signature
            )
        );

        return $this->getBlockChain()->addBlock($this->getCurrentTransactions(), $proof, $previousHash);
    }
}
