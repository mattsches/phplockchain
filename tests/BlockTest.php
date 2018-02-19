<?php

use Codeception\Stub;
use Codeception\Test\Unit;
use Mattsches\Block;
use Mattsches\Transaction;

/**
 * Class BlockTest
 */
class BlockTest extends Unit
{
    /**
     * @var Block
     */
    private $block;

    /**
     * Test setup
     */
    protected function _before()
    {
        $transactions = [
            Stub::make(Transaction::class),
        ];
        $this->block = new Block(1, $transactions, 123, '12ab');
    }

    /**
     * @test
     */
    public function itShouldCalculateAnHash()
    {
        $reflection = new \ReflectionClass($this->block);
        $property = $reflection->getProperty('timestamp');
        $property->setAccessible(true);
        $property->setValue($this->block, 1519048636);
        $result = $this->block->calculateHash();
        $this->assertSame('c12b17aa148e0fb4a835008880292ec97fd384c7ebacfbefc2610e663ca93101', $result);
    }

    /**
     * @test
     */
    public function itShouldReturnAJsonSerializedRepresentation()
    {
        $result = $this->block->jsonSerialize();
        $this->assertSame(1, $result['index']);
        $this->assertSame(123, $result['proof']);
        $this->assertSame('12ab', $result['previous_hash']);
        $this->assertInternalType('array', $result['transactions']);
    }
}
