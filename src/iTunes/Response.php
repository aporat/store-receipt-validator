<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
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
 *
 * @extends \ReceiptValidator\AbstractResponse<Transaction>
 */
final class Response extends AbstractResponse
{
    /** The latest Base64 encoded receipt for a subscription. */
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

    /** The app's bundle identifier. */
    public readonly ?string $bundleId;

    /** The unique identifier for the app. */
    public readonly ?string $appItemId;

    /** The date of the original app purchase. */
    public readonly ?CarbonImmutable $originalPurchaseDate;

    /** The date the validation request was sent. */
    public readonly ?CarbonImmutable $requestDate;

    /** The date the app receipt was created. */
    public readonly ?CarbonImmutable $receiptCreationDate;

    /** A flag indicating the request is retryable due to a temporary Apple server issue. */
    public readonly bool $isRetryable;

    /**
     * @param array<string, mixed> $data
     * @param Environment $environment
     * @throws ValidationException
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct($data, $environment);

        // Top-level scalars
        $this->isRetryable   = $this->toBool($data, 'is-retryable');
        $this->latestReceipt = $this->toString($data, 'latest_receipt');

        $receiptData = is_array($data['receipt'] ?? null) ? $data['receipt'] : [];

        $this->appItemId = $this->toString($receiptData, 'app_item_id');
        $this->bundleId  = $this->toString($receiptData, 'bundle_id') ?? $this->toString($receiptData, 'bid');

        // Dates (ms -> CarbonImmutable)
        $this->originalPurchaseDate = $this->toDateFromMs($receiptData, 'original_purchase_date_ms');
        $this->requestDate          = $this->toDateFromMs($receiptData, 'request_date_ms');
        $this->receiptCreationDate  = $this->toDateFromMs($receiptData, 'receipt_creation_date_ms');

        $latestReceiptInfo = [];
        if (isset($data['latest_receipt_info']) && is_array($data['latest_receipt_info'])) {
            foreach ($data['latest_receipt_info'] as $d) {
                $latestReceiptInfo[] = new Transaction((array) $d);
            }
        }
        $this->latestReceiptInfo = $latestReceiptInfo;

        $pendingRenewalInfo = [];
        if (isset($data['pending_renewal_info']) && is_array($data['pending_renewal_info'])) {
            foreach ($data['pending_renewal_info'] as $d) {
                $pendingRenewalInfo[] = new RenewalInfo((array) $d);
            }
        }
        $this->pendingRenewalInfo = $pendingRenewalInfo;

        // Transactions from receipt
        $receipt = $this->getRawData()['receipt'] ?? [];

        if (is_array($receipt) && isset($receipt['in_app']) && is_array($receipt['in_app'])) {
            foreach ($receipt['in_app'] as $tx) {
                $this->addTransaction(new Transaction((array) $tx));
            }
        } elseif (is_array($receipt) && array_key_exists('product_id', $receipt)) {
            // Legacy iOS 6 style (single, top-level transaction)
            $this->addTransaction(new Transaction($receipt));
        }
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }
    public function getLatestReceipt(): ?string
    {
        return $this->latestReceipt;
    }

    /** @return Transaction[] */
    public function getLatestReceiptInfo(): array
    {
        return $this->latestReceiptInfo;
    }

    /** @return RenewalInfo[] */
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

    /** @return CarbonInterface|null */
    public function getOriginalPurchaseDate(): ?CarbonInterface
    {
        return $this->originalPurchaseDate;
    }

    /** @return CarbonInterface|null */
    public function getRequestDate(): ?CarbonInterface
    {
        return $this->requestDate;
    }

    /** @return CarbonInterface|null */
    public function getReceiptCreationDate(): ?CarbonInterface
    {
        return $this->receiptCreationDate;
    }
}
