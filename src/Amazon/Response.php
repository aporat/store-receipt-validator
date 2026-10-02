<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use Carbon\CarbonInterface;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * The response from the Amazon Receipt Verification Service (RVS).
 *
 * RVS verifies one receipt per request, so the response holds exactly one
 * {@see Transaction} and delegates to it. The user ID is not part of Amazon's response
 * body; the validator supplies the one it sent in the request.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 *
 * @extends AbstractResponse<Transaction>
 */
final class Response extends AbstractResponse
{
    /** The verified purchase, or null when the response body was empty. */
    public readonly ?Transaction $transaction;

    /** The unique identifier for the purchase receipt. */
    public readonly ?string $receiptId;

    /** The product identifier (SKU) of the item purchased. */
    public readonly ?string $productId;

    /** The Amazon user ID the receipt was verified for. */
    public readonly ?string $userId;

    /** The type of product purchased. */
    public readonly ?ProductType $productType;

    /** The date the purchase was initiated. */
    public readonly ?CarbonInterface $purchaseDate;

    /** The date the purchase was cancelled or the subscription expired. */
    public readonly ?CarbonInterface $cancellationDate;

    /** Whether the purchase is a test transaction. */
    public readonly bool $testTransaction;

    /**
     * @param array<string, mixed> $data   The decoded RVS response body.
     * @param Environment $environment     The environment the request was sent to.
     * @param string|null $userId          The user ID sent in the request.
     */
    public function __construct(
        array $data = [],
        Environment $environment = Environment::PRODUCTION,
        ?string $userId = null,
    ) {
        parent::__construct($data, $environment);

        $this->transaction = $data !== [] ? new Transaction($data) : null;
        $this->userId      = $userId ?? $this->toString($data, 'userId');

        $this->receiptId        = $this->transaction?->getTransactionId();
        $this->productId        = $this->transaction?->getProductId();
        $this->productType      = $this->transaction?->getProductType();
        $this->purchaseDate     = $this->transaction?->getPurchaseDate();
        $this->cancellationDate = $this->transaction?->getCancellationDate();
        $this->testTransaction  = $this->transaction?->isTestTransaction() ?? false;

        if ($this->transaction !== null) {
            $this->setTransactions([$this->transaction]);
        }
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getReceiptId(): ?string
    {
        return $this->receiptId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getProductType(): ?ProductType
    {
        return $this->productType;
    }

    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }

    public function getCancellationDate(): ?CarbonInterface
    {
        return $this->cancellationDate;
    }

    public function getCancelReason(): ?CancelReason
    {
        return $this->transaction?->getCancelReason();
    }

    public function isTestTransaction(): bool
    {
        return $this->testTransaction;
    }

    public function isBetaProduct(): bool
    {
        return $this->transaction?->isBetaProduct() ?? false;
    }

    public function getCountryCode(): ?string
    {
        return $this->transaction?->getCountryCode();
    }

    /** @return array<Promotion> */
    public function getPromotions(): array
    {
        return $this->transaction?->getPromotions() ?? [];
    }

    public function isQuickSubscribe(): bool
    {
        return $this->transaction?->isQuickSubscribe() ?? false;
    }

    /** Whether the purchase was cancelled or the subscription expired. */
    public function isCanceled(): bool
    {
        return $this->transaction?->isCanceled() ?? false;
    }

    /** When access ends unless the subscription renews. See {@see Transaction::getExpiresAt()}. */
    public function getExpiresAt(): ?CarbonInterface
    {
        return $this->transaction?->getExpiresAt();
    }

    /** Whether the customer should currently have access. See {@see Transaction::isEntitled()}. */
    public function isEntitled(?CarbonInterface $now = null): bool
    {
        return $this->transaction?->isEntitled($now) ?? false;
    }
}
