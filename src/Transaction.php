<?php

namespace Mattsches;

use ParagonIE\Halite\Asymmetric\PublicKey;

/**
 * Class Transaction
 * @package Mattsches
 *
 * @todo Implement validation of Transaction on initialization
 * @todo Add custom message to Transaction
 */
class Transaction implements \JsonSerializable
{
    /**
     * @var PublicKey
     */
    private $sender;

    /**
     * @var PublicKey
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
     * @param PublicKey|string $sender
     * @param PublicKey|string $recipient
     * @param int $amount
     * @param string $signature
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     * @throws \SodiumException
     */
    public function __construct($sender, $recipient, int $amount, string $signature)
    {
        $this->sender = Util::getPublicKeyAsObject($sender);
        $this->recipient = Util::getPublicKeyAsObject($recipient);
        $this->amount = $amount;
        $this->signature = $signature;
    }

    /**
     * @return string
     * @throws \SodiumException
     */
    public function getHashableString(): string
    {
        return $this->amount.Util::getKeyAsString($this->sender).Util::getKeyAsString($this->recipient);
    }

    /**
     * Specify data which should be serialized to JSON
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * @throws \SodiumException
     */
    public function jsonSerialize(): array
    {
        return [
            'sender' => Util::getKeyAsString($this->sender),
            'recipient' => Util::getKeyAsString($this->recipient),
            'amount' => $this->amount,
            'signature' => $this->signature,
        ];
    }
}
