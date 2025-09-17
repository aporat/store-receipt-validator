<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * Encapsulates a single transaction from a legacy iTunes receipt.
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the 'in_app' array of a legacy iTunes receipt.
 */
final readonly class Transaction extends AbstractTransaction
{
    /** The unique identifier of the original transaction. */
    public ?string $originalTransactionId;

    /** The unique identifier for a transaction in the web order line item. */
    public ?string $webOrderLineItemId;

    /** The date and time of the purchase. */
    public ?CarbonImmutable $purchaseDate;

    /** The date and time of the original purchase. */
    public ?CarbonImmutable $originalPurchaseDate;

    /** The expiration date for a subscription. */
    public ?CarbonImmutable $expiresDate;

    /** The date a subscription or purchase was cancelled. */
    public ?CarbonImmutable $cancellationDate;

    /** A Boolean value that indicates whether the transaction is in a trial period. */
    public bool $isTrialPeriod;

    /** A Boolean value that indicates whether the transaction is in an introductory offer period. */
    public bool $isInIntroOfferPeriod;

    /** The identifier of a promotional offer. */
    public ?string $promotionalOfferId;

    /**
     * @param array<string, mixed> $data The raw data for a single transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct(
            rawData: $data,
            quantity: $this->toInt($data, 'quantity') ?? 0,
            productId: $this->toString($data, 'product_id'),
            transactionId: $this->toString($data, 'transaction_id'),
        );

        $this->originalTransactionId = $this->toString($data, 'original_transaction_id');
        $this->webOrderLineItemId    = $this->toString($data, 'web_order_line_item_id');
        $this->promotionalOfferId    = $this->toString($data, 'promotional_offer_id');

        $this->isTrialPeriod        = $this->toBool($data, 'is_trial_period');
        $this->isInIntroOfferPeriod = $this->toBool($data, 'is_in_intro_offer_period');

        $this->purchaseDate         = $this->toDateFromMs($data, 'purchase_date_ms');
        $this->originalPurchaseDate = $this->toDateFromMs($data, 'original_purchase_date_ms');
        $this->expiresDate          = $this->toDateFromMs($data, 'expires_date_ms');
        $this->cancellationDate     = $this->toDateFromMs($data, 'cancellation_date_ms');
    }

    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }
    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }
    public function getOriginalPurchaseDate(): ?CarbonInterface
    {
        return $this->originalPurchaseDate;
    }
    public function getExpiresDate(): ?CarbonInterface
    {
        return $this->expiresDate;
    }
    public function getCancellationDate(): ?CarbonInterface
    {
        return $this->cancellationDate;
    }

    public function isTrialPeriod(): bool
    {
        return $this->isTrialPeriod;
    }
    public function isInIntroOfferPeriod(): bool
    {
        return $this->isInIntroOfferPeriod;
    }
    public function getPromotionalOfferId(): ?string
    {
        return $this->promotionalOfferId;
    }

    public function hasExpired(): bool
    {
        return $this->expiresDate !== null && $this->expiresDate->isPast();
    }

    public function wasCanceled(): bool
    {
        return $this->cancellationDate !== null;
    }
}
