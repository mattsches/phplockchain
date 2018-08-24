<?php

namespace Mattsches;

/**
 * Class BlockChain
 * @package Mattsches
 */
class BlockChain implements \JsonSerializable
{
    /**
     * @var Block[]
     */
    private $blocks = [];

    /**
     * @var int
     */
    private $difficulty;

    /**
     * BlockChain constructor.
     * @param int $difficulty
     */
    public function __construct(int $difficulty)
    {
        $this->difficulty = $difficulty;
    }

    /**
     * @param array $transactions
     * @param int $proof
     * @param string $previousHash
     * @param int $timestamp
     * @return Block
     */
    public function addBlock(array $transactions, int $proof, string $previousHash, int $timestamp): Block
    {
        $block = new Block(\count($this->blocks) + 1, $transactions, $proof, $previousHash, $timestamp);
        $this->blocks[] = $block;

        return $block;
    }

    /**
     * @return Block|null
     */
    public function getLatestBlock(): ?Block
    {
        return end($this->blocks) ?: null;
    }

    /**
     * @param string $previousProofOfWork
     * @param string $previousHash The hash of the previous block
     * @return int
     */
    public function getProofOfWork(string $previousProofOfWork, string $previousHash): int
    {
        $proof = 0;
        while ($this->isValidProof($proof, $previousProofOfWork, $previousHash) === false) {
            $proof++;
        }

        return $proof;
    }

    /**
     * @param int $proof
     * @param string $previousProofOfWork
     * @param string $previousHash
     * @return bool
     */
    private function isValidProof(int $proof, string $previousProofOfWork, string $previousHash): bool
    {
        $guessHash = hash('sha256', $previousProofOfWork.$proof.$previousHash);

        return 0 === strpos($guessHash, str_repeat('0', $this->difficulty));
    }

    /**
     * @return bool
     */
//    public function resolveConflicts(): bool
//    {
//        $neighbours = $this->getNodes();
//        $maxLength = $this->getLength();
//        $newChain = null;
//        $client = new \GuzzleHttp\Client(); //TODO move to client?
//        foreach ($neighbours as $node) {
//            echo 'Querying node '.sprintf('http://%s/chain', $node).PHP_EOL;
//            try {
//                $response = $client->request('GET', sprintf('http://%s/chain', $node));
//                $values = json_decode($response->getBody()->getContents());
//                $length = $values->length;
//                $chain = $values->chain->blocks;
//                if ($length > $maxLength) {
//                    $maxLength = $length;
//                    $newChain = $chain;
//                }
//            } catch (GuzzleException $e) {
//                echo $e->getMessage().PHP_EOL;
//            }
//        }
//        //TODO
//        if ($newChain !== null) {
//            $this->blocks = $newChain;
//
//            return true;
//        }
//
//        return false;
//    }

//    /**
//     * @return int
//     */
//    private function getLength(): int
//    {
//        return \count($this->getBlocks());
//    }
//
    /**
     * @return Block[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Specify data which should be serialized to JSON
     * @return mixed data which can be serialized by <b>json_encode</b>,
     */
    public function jsonSerialize()
    {
        return [
            'blocks' => $this->blocks,
            'difficulty' => $this->difficulty,
        ];
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $lastBlock = $this->blocks[0];
        $currentIndex = 1;
        while ($currentIndex < \count($this->blocks)) {
            $block = $this->blocks[$currentIndex];
            if ($block->getPreviousHash() !== $lastBlock->calculateHash()) {
                echo 'INVALID'.PHP_EOL;

                return false;
            }
            if (!$this->isValidProof($block->getProofOfWork(), $lastBlock->getProofOfWork(), $block->getPreviousHash())) {
                echo 'INVALID 2'.PHP_EOL;

                return false;
            }
            $lastBlock = $block;
            ++$currentIndex;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    /**
     * @todo Use MerkleTree?
     * @param string $txid
     * @return Transaction|null
     */
    public function findTransaction(string $txid): ?Transaction
    {
        foreach ($this->blocks as $block) {
            foreach ($block->getTransactions() as $transaction) {
                if ($transaction->getTxid() === $txid) {
                    return $transaction;
                }
            }
        }
        return null;
    }

    /**
     * @param int $index
     * @return Block
     */
    public function getBlock(int $index): Block
    {
        return $this->blocks[$index];
    }
}
