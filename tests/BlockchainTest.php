<?php

use Codeception\Stub;
use Codeception\Test\Unit;
use Mattsches\Block;
use Mattsches\BlockChain;
use Mattsches\Transaction;

/**
 * Class BlockchainTest
 */
class BlockchainTest extends Unit
{
    /**
     * @var BlockChain
     */
    private $blockchain;

    /**
     *
     */
    protected function _before()
    {
        $this->blockchain = new BlockChain(4);
    }

    /**
     * @test
     */
    public function itShouldBeInitialized()
    {
        $this->assertInstanceOf(BlockChain::class, $this->blockchain);
    }

    /**
     * @test
     **/
    public function itShouldRegisterANode()
    {
        $this->assertCount(0, $this->blockchain->getNodes());
        $this->blockchain->registerNode('127.0.0.1:5555');
        $this->assertCount(1, $this->blockchain->getNodes());
    }

    /**
     * @test
     **/
    public function itShouldAddATransaction()
    {
        $transaction = Stub::make(Transaction::class);
        $result = $this->blockchain->addTransaction($transaction);
        $this->assertSame(2, $result);
    }

    /**
     * @test
     **/
    public function itShouldGetTheLatestBlock()
    {
        $result = $this->blockchain->getLatestBlock();
        $this->assertInstanceOf(Block::class, $result);
        $this->assertSame(1, $result->getIndex());
    }

    /**
     * @test
     **/
    public function itShouldGetProofOfWork()
    {
        $result = $this->blockchain->getProofOfWork('1', '12ab');
        $this->assertSame(66273, $result);
    }

    /**
     * @test
     **/
    public function itShouldGetJsonSerializedBlockchain()
    {
        $result = $this->blockchain->jsonSerialize();
        $this->assertInternalType('array', $result['blocks']);
        $this->assertInternalType('array', $result['currentTransactions']);
        $this->assertInternalType('array', $result['nodes']);
    }

    /**
     * @test
     **/
    public function itShouldAddABlock()
    {
        $result = $this->blockchain->addBlock(123, '12ab');
        $this->assertSame(2, $result->getIndex());
        $this->assertSame('12ab', $result->getPreviousHash());
        $this->assertSame(123, $result->getProofOfWork());
    }

    /**
     * @test
     **/
    public function itShouldBeValid()
    {
        $this->markTestIncomplete('Mock timestamp of last block');
        $this->blockchain->addBlock(123, '1');
        $this->assertTrue($this->blockchain->isValid());
    }

    /**
     * @test
     **/
    public function itShouldBeInvalid()
    {
        $this->blockchain->addBlock(123, '1');
        $this->assertFalse($this->blockchain->isValid());
    }
}
