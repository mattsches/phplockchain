<?php

namespace Mattsches;

use ParagonIE\Halite\Asymmetric\SignaturePublicKey;
use ParagonIE\Halite\Asymmetric\SignatureSecretKey;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

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
     * @var Uuid
     */
    private $txid;

    /**
     * @var SignaturePublicKey
     */
    private $sender;

    /**
     * @var SignaturePublicKey
     */
    private $recipient;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $signature;

    /**
     * Transaction constructor.
     *
     * @param UuidInterface $txid
     * @param SignaturePublicKey $sender
     * @param SignaturePublicKey $recipient
     * @param int $amount
     * @param string $signature
     */
    public function __construct(
        UuidInterface $txid,
        SignaturePublicKey $sender,
        SignaturePublicKey $recipient,
        int $amount,
        string $signature
    ) {
        $this->txid = $txid;
        $this->sender = $sender;
        $this->recipient = $recipient;
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
     * @return string
     */
    public function getTxid(): string
    {
        return $this->txid;
    }

    /**
     * @param SignatureSecretKey $recipientPrivateKey
     * @return bool
     * @throws \SodiumException
     */
    public function verifyAndDecrypt(SignatureSecretKey $recipientPrivateKey): bool
    {
        $decrypted = Util::verifyAndDecryptTransaction($this->signature, $this->sender, $recipientPrivateKey);

        return $decrypted === Util::getKeyAsString($this->sender).Util::getKeyAsString($this->recipient).$this->amount;
    }

    /**
     * Specify data which should be serialized to JSON
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * @throws \SodiumException
     */
    public function jsonSerialize(): array
    {
        return [
            'txid' => $this->txid->toString(),
            'sender' => Util::getKeyAsString($this->sender),
            'recipient' => Util::getKeyAsString($this->recipient),
            'amount' => $this->amount,
            'signature' => $this->signature,
        ];
    }
}
