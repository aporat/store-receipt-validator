<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * The contents of a StoreKit 1 app receipt that {@see AppReceiptVerifier} has
 * checked Apple's signature over. Only fields read from the signed bytes are
 * exposed, so everything here is as Apple issued it.
 */
final class AppReceipt
{
    /**
     * @param list<string> $transactionIds The transaction id of every in-app purchase, in receipt order.
     */
    public function __construct(
        private readonly string $bundleId,
        private readonly array $transactionIds,
    ) {
    }

    /**
     * The bundle identifier the receipt was issued for. Compare it with your
     * app's before trusting anything else in the receipt.
     */
    public function getBundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * The first in-app purchase's transaction id, or null for a receipt with no
     * purchases. Any transaction id of the account works with the App Store
     * Server API, so this is the one to look up.
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionIds[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getTransactionIds(): array
    {
        return $this->transactionIds;
    }
}
