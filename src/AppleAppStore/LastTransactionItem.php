<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;

/**
 * Represents a single entry in the lastTransactions array of a subscription group.
 *
 * Each item contains the most recent transaction and renewal info for one
 * subscription within a subscription group.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/lasttransactionsitem
 */
final readonly class LastTransactionItem
{
    /** The original transaction identifier for the subscription. */
    public string $originalTransactionId;

    /** The subscription's current status. */
    public SubscriptionStatus $status;

    /** The decoded transaction information, or null if the JWS could not be verified. */
    public ?Transaction $transactionInfo;

    /** The decoded renewal information, or null if the JWS could not be verified. */
    public ?RenewalInfo $renewalInfo;

    /**
     * @param array<string, mixed> $data Raw item data from the API response.
     */
    public function __construct(array $data)
    {
        $this->originalTransactionId = (string) ($data['originalTransactionId'] ?? '');
        $this->status = SubscriptionStatus::from((int) ($data['status'] ?? 0));

        $verifier = new TokenVerifier();

        if (isset($data['signedTransactionInfo']) && is_string($data['signedTransactionInfo'])) {
            $token = TokenGenerator::decodeToken($data['signedTransactionInfo']);
            $this->transactionInfo = $verifier->verify($token)
                ? new Transaction($token->claims()->all())
                : null;
        } else {
            $this->transactionInfo = null;
        }

        if (isset($data['signedRenewalInfo']) && is_string($data['signedRenewalInfo'])) {
            $token = TokenGenerator::decodeToken($data['signedRenewalInfo']);
            $this->renewalInfo = $verifier->verify($token)
                ? new RenewalInfo($token->claims()->all())
                : null;
        } else {
            $this->renewalInfo = null;
        }
    }

    public function getOriginalTransactionId(): string
    {
        return $this->originalTransactionId;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function getTransactionInfo(): ?Transaction
    {
        return $this->transactionInfo;
    }

    public function getRenewalInfo(): ?RenewalInfo
    {
        return $this->renewalInfo;
    }
}
