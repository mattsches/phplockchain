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
     * @var SignatureKeyPair
     */
    protected $keyPair;

    /**
     * Client constructor.
     * @param SignatureKeyPair $keyPair
     */
    public function __construct(SignatureKeyPair $keyPair)
    {
        $this->keyPair = $keyPair;
    }

    /**
     * @return BlockChain|null
     */
    public function getBlockChain(): ?BlockChain
    {
        return $this->blockChain;
    }

    /**
     * @return SignatureKeyPair
     */
    public function getKeyPair(): SignatureKeyPair
    {
        return $this->keyPair;
    }
}
