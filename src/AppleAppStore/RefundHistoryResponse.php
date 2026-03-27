<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Get Refund History endpoint.
 *
 * Contains up to 20 refunded or revoked transactions per page, sorted by
 * revocation date ascending. Use {@see $revision} with subsequent requests
 * to paginate through the full history.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/get-refund-history
 */
final class RefundHistoryResponse
{
    use ValueCasting;

    /**
     * The decoded refunded transactions for this page.
     *
     * @var list<Transaction>
     */
    public readonly array $signedTransactions;

    /** A token to pass as the revision query parameter on the next request, or null when unavailable. */
    public readonly ?string $revision;

    /** Whether more pages of refund history are available. */
    public readonly bool $hasMore;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->revision = $this->toString($data, 'revision');
        $this->hasMore  = $this->toBool($data, 'hasMore');

        $raw = is_array($data['signedTransactions'] ?? null) ? $data['signedTransactions'] : [];

        $transactions = [];
        foreach ($raw as $jws) {
            if (!is_string($jws) || $jws === '') {
                continue;
            }
            try {
                $token    = TokenGenerator::decodeToken($jws);
                $verifier = new TokenVerifier();
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
     * @return list<Transaction>
     */
    public function getSignedTransactions(): array
    {
        return $this->signedTransactions;
    }

    public function getRevision(): ?string
    {
        return $this->revision;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }
}
