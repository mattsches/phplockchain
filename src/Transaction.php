<?php

namespace Mattsches;

/**
 * Class Transaction
 * @package Mattsches
 *
 * @todo Implement validation of Transaction on initialization
 */
class Transaction implements \JsonSerializable
{
    /**
     * @var string
     */
    private $sender;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $signature;

    /**
     * Transaction constructor.
     * @param string $sender
     * @param string $recipient
     * @param int $amount
     * @param string $signature
     */
    public function __construct(string $sender, string $recipient, int $amount, string $signature)
    {
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getHashableString(): string
    {
        return $this->amount.$this->sender.$this->recipient;
    }

    /**
     * Specify data which should be serialized to JSON
     * @return mixed data which can be serialized by <b>json_encode</b>,
     */
    public function jsonSerialize(): array
    {
        return [
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'amount' => $this->amount,
            'signature' => $this->signature,
        ];
    }
}
