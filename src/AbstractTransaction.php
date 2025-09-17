<?php

declare(strict_types=1);

namespace ReceiptValidator;

use ReceiptValidator\Support\ValueCasting;

/**
 * Provides a base structure for individual transaction objects.
 *
 * This abstract class standardizes how transaction data is represented,
 * ensuring that transaction objects from all stores are immutable and
 * offer a consistent interface for accessing common fields.
 */
abstract readonly class AbstractTransaction
{
    use ValueCasting;

    /**
     * Constructs the transaction object.
     *
     * Child classes should pass their parsed values to this constructor
     * and then initialize their own readonly properties.
     *
     * @param array<string, mixed> $rawData The raw data for a single transaction.
     */
    public function __construct(
        protected array $rawData = [],
        protected int $quantity = 1,
        protected ?string $productId = null,
        protected ?string $transactionId = null,
    ) {
    }

    /**
     * Returns the original, unprocessed data for the transaction.
     *
     * @return array<string, mixed>
     */
    final public function getRawData(): array
    {
        return $this->rawData;
    }

    /** Returns the number of items purchased. */
    final public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** Returns the unique identifier of the product. */
    final public function getProductId(): ?string
    {
        return $this->productId;
    }

    /** Returns the unique identifier of the transaction. */
    final public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
