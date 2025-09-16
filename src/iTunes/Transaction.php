<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;

/**
 * Encapsulates a single transaction from a legacy iTunes receipt.
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the 'in_app' array of a legacy iTunes receipt.
 */
final class Transaction extends AbstractTransaction
{
    /**
     * The unique identifier of the original transaction.
     */
    public ?string $originalTransactionId;

    /**
     * The unique identifier for a transaction in the web order line item.
     */
    public ?string $webOrderLineItemId;

    /**
     * The date and time of the purchase.
     */
    public ?Carbon $purchaseDate;

    /**
     * The date and time of the original purchase.
     */
    public ?Carbon $originalPurchaseDate;

    /**
     * The expiration date for a subscription.
     */
    public ?Carbon $expiresDate;

    /**
     * The date a subscription or purchase was cancelled.
     */
    public ?Carbon $cancellationDate;

    /**
     * A Boolean value that indicates whether the transaction is in a trial period.
     */
    public ?bool $isTrialPeriod;

    /**
     * A Boolean value that indicates whether the transaction is in an introductory offer period.
     */
    public ?bool $isInIntroOfferPeriod;

    /**
     * The identifier of a promotional offer.
     */
    public ?string $promotionalOfferId;

    /**
     * Constructs the Transaction object and initializes its state.
     *
     * @param array<string, mixed> $data The raw data for a single transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Initialize parent's properties from iTunes-specific fields.
        $this->quantity = (int) ($data['quantity'] ?? 0);
        $this->productId = $data['product_id'] ?? null;
        $this->transactionId = $data['transaction_id'] ?? null;

        // Initialize properties specific to this transaction type.
        $this->originalTransactionId = $data['original_transaction_id'] ?? null;
        $this->webOrderLineItemId = $data['web_order_line_item_id'] ?? null;
        $this->promotionalOfferId = $data['promotional_offer_id'] ?? null;

        $this->isTrialPeriod = isset($data['is_trial_period']) ? filter_var($data['is_trial_period'], FILTER_VALIDATE_BOOLEAN) : null;
        $this->isInIntroOfferPeriod = isset($data['is_in_intro_offer_period']) ? filter_var($data['is_in_intro_offer_period'], FILTER_VALIDATE_BOOLEAN) : null;

        // Parse millisecond timestamps into Carbon objects.
        $this->purchaseDate = isset($data['purchase_date_ms']) ? Carbon::createFromTimestampMs($data['purchase_date_ms']) : null;
        $this->originalPurchaseDate = isset($data['original_purchase_date_ms']) ? Carbon::createFromTimestampMs($data['original_purchase_date_ms']) : null;
        $this->expiresDate = isset($data['expires_date_ms']) ? Carbon::createFromTimestampMs($data['expires_date_ms']) : null;
        $this->cancellationDate = isset($data['cancellation_date_ms']) ? Carbon::createFromTimestampMs($data['cancellation_date_ms']) : null;
    }

    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }

    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchaseDate;
    }

    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->originalPurchaseDate;
    }

    public function getExpiresDate(): ?Carbon
    {
        return $this->expiresDate;
    }

    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellationDate;
    }

    public function isTrialPeriod(): ?bool
    {
        return $this->isTrialPeriod;
    }

    public function isInIntroOfferPeriod(): ?bool
    {
        return $this->isInIntroOfferPeriod;
    }

    public function getPromotionalOfferId(): ?string
    {
        return $this->promotionalOfferId;
    }

    /**
     * Checks if the subscription has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expiresDate !== null && $this->expiresDate->isPast();
    }

    /**
     * Checks if the subscription was cancelled.
     */
    public function wasCanceled(): bool
    {
        return $this->cancellationDate !== null;
    }
}
