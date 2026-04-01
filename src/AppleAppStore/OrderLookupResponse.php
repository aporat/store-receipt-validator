<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Look Up Order ID endpoint.
 *
 * Contains the status of the order and a list of signed transactions associated
 * with the given App Store order ID.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/orderlookupresponse
 */
final class OrderLookupResponse
{
    use ValueCasting;

    /**
     * The lookup status that indicates whether the order ID is valid.
     *
     * - 0: The order ID is valid and the signedTransactions array is populated.
     * - 1: The order ID is invalid or not found; signedTransactions will be empty.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/orderlookupstatus
     */
    public readonly int $status;

    /**
     * The decoded transactions associated with this order.
     *
     * @var list<Transaction>
     */
    public readonly array $signedTransactions;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->status = $this->toInt($data, 'status') ?? 0;

        $raw          = is_array($data['signedTransactions'] ?? null) ? $data['signedTransactions'] : [];
        $transactions = [];
        $verifier     = new TokenVerifier();

        foreach ($raw as $jws) {
            if (!is_string($jws) || $jws === '') {
                continue;
            }
            try {
                $token = TokenGenerator::decodeToken($jws);
                if ($verifier->verify($token)) {
                    $transactions[] = new Transaction($token->claims()->all());
                }
            } catch (\Throwable) {
                // Skip tokens that cannot be decoded or verified
            }
        }

        $this->signedTransactions = $transactions;
    }

    /**
     * Returns true when the order ID was found and transactions are available.
     */
    public function isValid(): bool
    {
        return $this->status === 0;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return list<Transaction>
     */
    public function getSignedTransactions(): array
    {
        return $this->signedTransactions;
    }
}
