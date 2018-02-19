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
     */
    public function __construct(int $index, array $transactions, int $proofOfWork, string $previousHash)
    {
        $this->index = $index;
        $this->transactions = $transactions;
        $this->proofOfWork = $proofOfWork;
        $this->previousHash = $previousHash;
        $this->timestamp = time();
    }

    /**
     * @return string
     * @throws CannotPerformOperation
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
        $rootHash = $merkleTree->getRoot();

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
}
