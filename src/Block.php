<?php

namespace Mattsches;

use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Structure\MerkleTree;
use ParagonIE\Halite\Structure\Node;

/**
 * Class Block
 * @package Mattsches
 */
class Block implements \JsonSerializable
{
    /**
     * @var int
     */
    private $index;

    /**
     * @var Transaction[]
     */
    private $transactions;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $proofOfWork;

    /**
     * @var string
     */
    private $previousHash;

    /**
     * Block constructor.
     * @param int $index
     * @param array $transactions
     * @param int $proofOfWork
     * @param string $previousHash
     * @param int $timestamp
     */
    public function __construct(int $index, array $transactions, int $proofOfWork, string $previousHash, int $timestamp)
    {
        $this->index = $index;
        $this->transactions = $transactions;
        $this->proofOfWork = $proofOfWork;
        $this->previousHash = $previousHash;
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function calculateHash(): string
    {
        $treeNodes = array_map(
            function (Transaction $transaction) {
                return new Node($transaction->getHashableString());
            },
            $this->transactions
        );
        $merkleTree = new MerkleTree(...$treeNodes);
        try {
            $rootHash = $merkleTree->getRoot();
        } catch (CannotPerformOperation|\TypeError $e) {
            die($e->getMessage());
        }

        return hash('sha256', $this->index.$rootHash.$this->proofOfWork.$this->previousHash.$this->timestamp);
    }

    /**
     * Specify data which should be serialized to JSON
     * @return mixed data which can be serialized by <b>json_encode</b>,
     */
    public function jsonSerialize(): array
    {
        return [
            'index' => $this->getIndex(),
            'proof' => $this->getProofOfWork(),
            'previous_hash' => $this->getPreviousHash(),
            'transactions' => $this->getTransactions(),
            'hash' => $this->calculateHash(),
            'timestamp' => $this->getTimestamp(),
        ];
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return int
     */
    public function getProofOfWork(): int
    {
        return $this->proofOfWork;
    }

    /**
     * @return string
     */
    public function getPreviousHash(): string
    {
        return $this->previousHash;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
