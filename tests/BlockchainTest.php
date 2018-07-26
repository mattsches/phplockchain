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
    public function itShouldBeInitialized(): void
    {
        $this->assertInstanceOf(BlockChain::class, $this->blockchain);
    }

    /**
     * @test
     **/
    public function itShouldRegisterANode(): void
    {
        $this->assertCount(0, $this->blockchain->getNodes());
        $this->blockchain->registerNode('127.0.0.1:5555');
        $this->assertCount(1, $this->blockchain->getNodes());
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function itShouldAddATransaction(): void
    {
        /** @var Transaction $transaction */
        $transaction = Stub::make(Transaction::class);
        $result = $this->blockchain->addTransaction($transaction);
        $this->assertSame(1, $result);
    }

    /**
     * @test
     **/
    public function itShouldGetTheLatestBlock(): void
    {
        $this->assertNull($this->blockchain->getLatestBlock());
        $this->blockchain->addBlock(100, '');
        $result = $this->blockchain->getLatestBlock();
        $this->assertInstanceOf(Block::class, $result);
        $this->assertSame(1, $result->getIndex());
    }

    /**
     * @test
     **/
    public function itShouldGetProofOfWork(): void
    {
        $result = $this->blockchain->getProofOfWork('1', '12ab');
        $this->assertSame(66273, $result);
    }

    /**
     * @test
     **/
    public function itShouldGetJsonSerializedBlockchain(): void
    {
        $result = $this->blockchain->jsonSerialize();
        $this->assertInternalType('array', $result['blocks']);
        $this->assertInternalType('array', $result['currentTransactions']);
        $this->assertInternalType('array', $result['nodes']);
    }

    /**
     * @test
     **/
    public function itShouldAddABlock(): void
    {
        $result = $this->blockchain->addBlock(123, '12ab');
        $this->assertSame(1, $result->getIndex());
        $this->assertSame('12ab', $result->getPreviousHash());
        $this->assertSame(123, $result->getProofOfWork());
    }

    /**
     * @test
     *
     * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
     */
    public function itShouldBeValid(): void
    {
        $this->markTestIncomplete('Mock timestamp of last block');
        $this->blockchain->addBlock(123, '1');
        $this->assertTrue($this->blockchain->isValid());
    }

    /**
     * @test
     *
     * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
     */
    public function itShouldBeInvalid(): void
    {
        $this->markTestIncomplete('todo fix');
        $this->blockchain->addBlock(123, '1');
        $this->assertFalse($this->blockchain->isValid());
    }

    /**
     * @test
     **/
    public function itShouldGetDifficulty(): void
    {
        $this->assertSame(4, $this->blockchain->getDifficulty());
    }
}
