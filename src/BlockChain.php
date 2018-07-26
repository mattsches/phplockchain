<?php

namespace Mattsches;

use GuzzleHttp\Exception\GuzzleException;

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
     * @var Transaction[]
     */
    private $currentTransactions = [];

    /**
     * @var array
     */
    private $nodes = [];

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
     * @param $proof
     * @param $previousHash
     * @return Block
     */
    public function addBlock($proof, $previousHash): Block
    {
        $block = new Block(\count($this->blocks) + 1, $this->currentTransactions, $proof, $previousHash);
        $this->currentTransactions = [];
        $this->blocks[] = $block;

        return $block;
    }

    /**
     * @param $address
     */
    public function registerNode($address): void
    {
        //TODO parse address
        $this->nodes[] = $address;
    }

    /**
     * @param Transaction $transaction The transaction that will be added
     * @return int Index of the block to which the transaction will be added, ie the next block
     */
    public function addTransaction(Transaction $transaction): int
    {
        $this->currentTransactions[] = $transaction;

        $latestBlock = $this->getLatestBlock();
        if ($latestBlock instanceof Block) {
            return $latestBlock->getIndex() + 1;
        }

        return 1;
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
     * @param string $previousHash
     * @return int
     */
    public function getProofOfWork(string $previousProofOfWork, string $previousHash): int
    {
        $proof = 0;
        while ($this->validProof($previousProofOfWork, $proof, $previousHash) === false) {
            $proof++;
        }

        return $proof;
    }

    /**
     * @param $lastProof
     * @param $proof
     * @param $lastHash
     * @return bool
     */
    private function validProof($lastProof, $proof, $lastHash): bool
    {
        $guessHash = hash('sha256', $lastProof.$proof.$lastHash);

        return 0 === strpos($guessHash, str_repeat('0', $this->difficulty));
    }

    /**
     * @return bool
     */
    public function resolveConflicts(): bool
    {
        $neighbours = $this->getNodes();
        $maxLength = $this->getLength();
        $newChain = null;
        $client = new \GuzzleHttp\Client(); //TODO move to client?
        foreach ($neighbours as $node) {
            echo 'Querying node '.sprintf('http://%s/chain', $node).PHP_EOL;
            try {
                $response = $client->request('GET', sprintf('http://%s/chain', $node));
                $values = json_decode($response->getBody()->getContents());
                $length = $values->length;
                $chain = $values->chain->blocks;
                if ($length > $maxLength) {
                    $maxLength = $length;
                    $newChain = $chain;
                }
            } catch (GuzzleException $e) {
                echo $e->getMessage().PHP_EOL;
            }
        }
        //TODO
        if ($newChain !== null) {
            $this->blocks = $newChain;

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return int
     */
    private function getLength(): int
    {
        return \count($this->getBlocks());
    }

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
            'currentTransactions' => $this->currentTransactions,
            'nodes' => $this->nodes,
        ];
    }

    /**
     * @return bool
     * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
     */
    public function isValid(): bool
    {
        $lastBlock = $this->blocks[0];
        $currentIndex = 1;
        while ($currentIndex < \count($this->blocks)) {
            $block = $this->blocks[$currentIndex];
            if ($block->getPreviousHash() !== $lastBlock->calculateHash()) {
//                $foo = $lastBlock->calculateHash(); //TODO
                echo 'INVALID'.PHP_EOL;

                return false;
            }
            if (!$this->validProof($lastBlock->getProofOfWork(), $block->getProofOfWork(), $block->getPreviousHash())) {
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
}
