<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * Encapsulates the response from the Amazon RVS (Receipt Verification Service).
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the Amazon RVS response.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 *
 * @extends AbstractResponse<Transaction>
 */
final class Response extends AbstractResponse
{
    /** The unique identifier for the purchase receipt. */
    public readonly ?string $receiptId;

    /** The product identifier (SKU) of the item purchased. */
    public readonly ?string $productId;

    /** The unique identifier for the customer. */
    public readonly ?string $userId;

    /** The type of product purchased (e.g., 'CONSUMABLE', 'ENTITLED', 'SUBSCRIPTION'). */
    public readonly ?string $productType;

    /** The date the purchase was initiated. */
    public readonly ?CarbonImmutable $purchaseDate;

    /** The date the subscription or entitlement was cancelled. */
    public readonly ?CarbonImmutable $cancellationDate;

    /** A flag indicating if the purchase is a test transaction. */
    public readonly bool $testTransaction;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct($data, $environment);

        $this->receiptId        = $this->toString($data, 'receiptId');
        $this->productId        = $this->toString($data, 'productId');
        $this->userId           = $this->toString($data, 'userId');
        $this->productType      = $this->toString($data, 'productType');
        $this->testTransaction  = $this->toBool($data, 'testTransaction');
        $this->purchaseDate     = $this->toDateFromMs($data, 'purchaseDate');
        $this->cancellationDate = $this->toDateFromMs($data, 'cancelDate');

        if (!empty($data)) {
            $this->setTransactions([new Transaction($data)]);
        }
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

    /** @return CarbonInterface|null */
    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }

    /** @return CarbonInterface|null */
    public function getCancellationDate(): ?CarbonInterface
    {
        return $this->cancellationDate;
    }

    public function isTestTransaction(): bool
    {
        return $this->testTransaction;
    }
}
