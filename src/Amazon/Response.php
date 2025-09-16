<?php

namespace ReceiptValidator\Amazon;

use Carbon\Carbon;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Encapsulates the response from the Amazon RVS (Receipt Verification Service).
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the Amazon RVS response.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 */
final class Response extends AbstractResponse
{
    /**
     * The unique identifier for the purchase receipt.
     */
    public readonly ?string $receiptId;

    /**
     * The product identifier (SKU) of the item purchased.
     */
    public readonly ?string $productId;

    /**
     * The unique identifier for the customer.
     */
    public readonly ?string $userId;

    /**
     * The type of product purchased (e.g., 'CONSUMABLE', 'ENTITLED', 'SUBSCRIPTION').
     */
    public readonly ?string $productType;

    /**
     * The date the purchase was initiated.
     */
    public readonly ?Carbon $purchaseDate;

    /**
     * The date the subscription or entitlement was cancelled.
     */
    public readonly ?Carbon $cancellationDate;

    /**
     * A flag indicating if the purchase is a test transaction.
     */
    public readonly bool $testTransaction;

    /**
     * Overridden constructor to initialize readonly properties before parsing.
     *
     * @param array<string, mixed> $data
     * @param Environment $environment
     * @throws ValidationException
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct($data, $environment);

        $this->receiptId = $data['receiptId'] ?? null;
        $this->productId = $data['productId'] ?? null;
        $this->userId = $data['userId'] ?? null;
        $this->productType = $data['productType'] ?? null;
        $this->testTransaction = $data['testTransaction'] ?? false;

        if (!empty($data['purchaseDate'])) {
            $this->purchaseDate = Carbon::createFromTimestampMs($data['purchaseDate']);
        } else {
            $this->purchaseDate = null;
        }

        if (!empty($data['cancelDate'])) {
            $this->cancellationDate = Carbon::createFromTimestampMs($data['cancelDate']);
        } else {
            $this->cancellationDate = null;
        }

        // For Amazon, the response itself represents a single transaction.
        // We only create it if the response data is not empty.
        if (!empty($this->rawData)) {
            $this->transactions = [new Transaction($this->rawData)];
        }
    }

    /**
     * @return array<Transaction>
     */
    public function getTransactions(): array
    {
        /** @var array<Transaction> */
        return parent::getTransactions();
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

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchaseDate;
    }

    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellationDate;
    }

    public function isTestTransaction(): bool
    {
        return $this->testTransaction;
    }
}
