<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\AbstractResponse;
use ReceiptValidator\AppleAppStore\JWT\TokenGenerator;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;

/**
 * Encapsulates a decoded response from the App Store Server API.
 *
 * This immutable data object provides structured access to the transaction history
 * and other metadata returned by Apple's API.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/transactionhistoryresponse
 *
 * @extends AbstractResponse<Transaction>
 */
final class Response extends AbstractResponse
{
    /** A string that indicates the version of the response. */
    public readonly ?string $revision;

    /** The bundle identifier of the app. */
    public readonly ?string $bundleId;

    /** The unique identifier of the app in the App Store. */
    public readonly ?int $appAppleId;

    /** A Boolean value that indicates whether the App Store has more transaction data to send. */
    public readonly bool $hasMore;

    /**
     * @param array<string, mixed> $data The raw decoded JSON data from the API response.
     */
    public function __construct(array $data = [])
    {
        $definitiveEnvironment = $this->toEnvironment($data, 'environment');
        parent::__construct($data, $definitiveEnvironment);

        $this->revision   = $this->toString($data, 'revision');
        $this->bundleId   = $this->toString($data, 'bundleId');
        $this->appAppleId = $this->toInt($data, 'appAppleId');
        $this->hasMore    = $this->toBool($data, 'hasMore');

        $verifier = new TokenVerifier();
        $signedTransactions = $data['signedTransactions'] ?? [];

        if (is_array($signedTransactions)) {
            foreach ($signedTransactions as $signedTransaction) {
                $token = TokenGenerator::decodeToken($signedTransaction);

                if ($verifier->verify($token)) {
                    $this->addTransaction(new Transaction($token->claims()->all()));
                }
            }
        }
    }

    public function getRevision(): ?string
    {
        return $this->revision;
    }
    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }
    public function getAppAppleId(): ?int
    {
        return $this->appAppleId;
    }
    public function hasMore(): bool
    {
        return $this->hasMore;
    }
}
