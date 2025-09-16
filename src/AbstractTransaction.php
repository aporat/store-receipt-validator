<?php

namespace ReceiptValidator;

use ReceiptValidator\Exceptions\ValidationException;

/**
 * Provides a base structure for individual transaction objects.
 *
 * This abstract class standardizes how transaction data is represented,
 * ensuring that transaction objects from all stores are immutable and
 * offer a consistent interface for accessing common fields.
 */
abstract class AbstractTransaction
{
    /**
     * The original, unprocessed data for this transaction.
     *
     * @var array<string, mixed>
     */
    protected readonly array $rawData;

    /**
     * The number of items purchased in the transaction.
     */
    protected int $quantity;

    /**
     * The unique identifier of the product.
     */
    protected ?string $productId;

    /**
     * The unique identifier of the transaction.
     */
    protected ?string $transactionId;

    /**
     * Constructs the transaction object.
     *
     * Child classes should call `parent::__construct($data)` and then
     * initialize their own readonly properties.
     *
     * @param array<string, mixed> $data The raw data for a single transaction.
     */
    public function __construct(array $data = [])
    {
        $this->rawData = $data;
    }

    /**
     * Returns the original, unprocessed data for the transaction.
     *
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * Returns the number of items purchased.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Returns the unique identifier of the product.
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * Returns the unique identifier of the transaction.
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
