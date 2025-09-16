<?php

namespace ReceiptValidator\Amazon;

use DateTimeImmutable;
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
    public readonly ?DateTimeImmutable $purchaseDate;

    /**
     * The date the subscription or entitlement was cancelled.
     */
    public readonly ?DateTimeImmutable $cancelDate;

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
        $this->receiptId = $data['receiptId'] ?? null;
        $this->productId = $data['productId'] ?? null;
        $this->userId = $data['userId'] ?? null;
        $this->productType = $data['productType'] ?? null;
        $this->testTransaction = $data['testTransaction'] ?? false;
        $this->purchaseDate = isset($data['purchaseDate']) ? (new DateTimeImmutable())->setTimestamp((int)($data['purchaseDate'] / 1000)) : null;
        $this->cancelDate = isset($data['cancelDate']) ? (new DateTimeImmutable())->setTimestamp((int)($data['cancelDate'] / 1000)) : null;

        parent::__construct($data, $environment);
    }

    /**
     * Parses the raw response data to create a transaction.
     */
    protected function parse(): void
    {
        // For Amazon, the response itself represents a single transaction.
        // We only create it if the response data is not empty.
        if (!empty($this->rawData)) {
            $this->transactions = [new Transaction($this->rawData)];
        }
    }

    // --- All existing public methods are preserved ---

    /**
     * @return array<Transaction>
     */
    public function getTransactions(): array
    {
        /** @var array<Transaction> */
        return parent::getTransactions();
    }

    // --- New getter methods for convenience ---

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

    public function getPurchaseDate(): ?DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function getCancelDate(): ?DateTimeImmutable
    {
        return $this->cancelDate;
    }

    public function isTestTransaction(): bool
    {
        return $this->testTransaction;
    }
}
