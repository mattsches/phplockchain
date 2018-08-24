<?php

namespace Mattsches;

use GuzzleHttp\Client as HttpClient;
use ParagonIE\Halite\SignatureKeyPair;
use Ramsey\Uuid\Uuid;

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
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Client constructor.
     * @param SignatureKeyPair $keyPair
     * @param HttpClient $httpClient
     */
    public function __construct(SignatureKeyPair $keyPair, HttpClient $httpClient)
    {
        $this->keyPair = $keyPair;
        $this->httpClient = $httpClient;
        if (!$this->isMaster()) {
            try {
                $this->blockChain = $this->downloadBlockChain();
            } catch (\Exception $exception) {
                die('Could not download blockchain from master. Client setup failed.');
            }
        }
    }

    /**
     * @return bool
     */
    public function isMaster(): bool
    {
        return false;
    }

    /**
     * @return BlockChain
     * @throws \Ramsey\Uuid\Exception\InvalidUuidStringException
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     * @throws \SodiumException
     */
    private function downloadBlockChain(): BlockChain
    {
        $blockChainInfo = json_decode($this->httpClient->get('http://127.0.0.1:5001/getblockchaininfo')->getBody()->getContents());
        $blockChain = new BlockChain($blockChainInfo->difficulty);
        for ($i = 0; $i < $blockChainInfo->blocks; $i++) {
            $block = json_decode($this->httpClient->post('http://127.0.0.1:5001/getblock', ['json' => ['index' => $i]])->getBody()->getContents());
            foreach ($block->transactions as &$t) {
                $t = new Transaction(Uuid::fromString($t->txid), Util::getPublicKeyAsObject($t->sender), Util::getPublicKeyAsObject($t->recipient), $t->amount, $t->signature);
            }
            $blockChain->addBlock($block->transactions, $block->proof, $block->previous_hash, $block->timestamp);
        }
        return $blockChain;
    }

    /**
     * @return BlockChain
     */
    public function getBlockChain(): BlockChain
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
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     * @throws \InvalidArgumentException
     * @throws \SodiumException
     * @throws \Exception
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
                Uuid::uuid4(),
                $clientPublicKey,
                $clientPublicKey,
                $amount,
                $signature
            )
        );

        return $this->getBlockChain()->addBlock($this->getCurrentTransactions(), $proof, $previousHash, time());
    }
}
