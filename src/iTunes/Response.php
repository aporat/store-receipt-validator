<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Encapsulates the response from the legacy 'verifyReceipt' iTunes endpoint.
 *
 * This immutable data object provides structured access to receipt attributes,
 * transactions, and subscription information.
 *
 * @see https://developer.apple.com/documentation/appstorereceipts/responsebody
 */
final class Response extends AbstractResponse
{
    /**
     * The latest Base64 encoded receipt for a subscription.
     */
    public readonly ?string $latestReceipt;

    /**
     * An array of the latest transaction information for an auto-renewable subscription.
     *
     * @var Transaction[]
     */
    public readonly array $latestReceiptInfo;

    /**
     * An array of pending renewal information for an auto-renewable subscription.
     *
     * @var RenewalInfo[]
     */
    public readonly array $pendingRenewalInfo;

    /**
     * The app's bundle identifier.
     */
    public readonly ?string $bundleId;

    /**
     * The unique identifier for the app.
     */
    public readonly ?string $appItemId;

    /**
     * The date of the original app purchase.
     */
    public readonly ?Carbon $originalPurchaseDate;

    /**
     * The date the validation request was sent.
     */
    public readonly ?Carbon $requestDate;

    /**
     * The date the app receipt was created.
     */
    public readonly ?Carbon $receiptCreationDate;

    /**
     * A flag indicating the request is retryable due to a temporary Apple server issue.
     */
    public readonly bool $isRetryable;

    /**
     * Overridden constructor to initialize readonly properties before parsing.
     *
     * @param array<string, mixed> $data
     * @param Environment $environment
     * @throws ValidationException
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        // Initialize all readonly properties from the data array.
        $this->isRetryable = $data['is-retryable'] ?? false;
        $this->latestReceipt = $data['latest_receipt'] ?? null;

        $receiptData = $data['receipt'] ?? [];
        $this->appItemId = $receiptData['app_item_id'] ?? null;
        $this->bundleId = $receiptData['bundle_id'] ?? $receiptData['bid'] ?? null;
        $this->originalPurchaseDate = isset($receiptData['original_purchase_date_ms']) ? Carbon::createFromTimestampMs($receiptData['original_purchase_date_ms']) : null;
        $this->requestDate = isset($receiptData['request_date_ms']) ? Carbon::createFromTimestampMs($receiptData['request_date_ms']) : null;
        $this->receiptCreationDate = isset($receiptData['receipt_creation_date_ms']) ? Carbon::createFromTimestampMs($receiptData['receipt_creation_date_ms']) : null;

        $this->latestReceiptInfo = isset($data['latest_receipt_info']) ? array_map(fn($d) => new Transaction($d), $data['latest_receipt_info']) : [];
        $this->pendingRenewalInfo = isset($data['pending_renewal_info']) ? array_map(fn($d) => new RenewalInfo($d), $data['pending_renewal_info']) : [];

        parent::__construct($data, $environment);
    }

    /**
     * Parses the transaction data from the raw response.
     */
    protected function parse(): void
    {
        $receipt = $this->rawData['receipt'] ?? [];

        if (isset($receipt['in_app'])) {
            foreach ($receipt['in_app'] as $transactionData) {
                $this->transactions[] = new Transaction($transactionData);
            }
        } elseif (array_key_exists('product_id', $receipt)) {
            // Handle legacy "iOS 6" style receipts where the transaction
            // data is at the top level of the 'receipt' object.
            $this->transactions[] = new Transaction($receipt);
        }
    }

    // --- All existing public methods are preserved for backward compatibility ---

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    public function getLatestReceipt(): ?string
    {
        return $this->latestReceipt;
    }

    /**
     * @return Transaction[]
     */
    public function getLatestReceiptInfo(): array
    {
        return $this->latestReceiptInfo;
    }

    /**
     * @return RenewalInfo[]
     */
    public function getPendingRenewalInfo(): array
    {
        return $this->pendingRenewalInfo;
    }

    public function getBundleId(): ?string
    {
        return $this->bundleId;
    }

    public function getAppItemId(): ?string
    {
        return $this->appItemId;
    }

    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->originalPurchaseDate;
    }

    public function getRequestDate(): ?Carbon
    {
        return $this->requestDate;
    }

    public function getReceiptCreationDate(): ?Carbon
    {
        return $this->receiptCreationDate;
    }
}
